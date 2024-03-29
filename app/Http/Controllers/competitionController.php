<?php

namespace App\Http\Controllers;

use App\Helpers\ControllerHelper;
use App\Models\competition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Exceptions\MultiException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;

class competitionController extends Controller
{
    /**
     * @var int The maximum of results per page
     */
    const RESULTS_LIMIT = 100;

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        // get values form request -> to int -> to absolute value
        // note pages start counting at 1
        $id = abs(intval($request->input('id')));
        $page = abs(intval($request->input('page')));
        $limit  = abs(intval($request->input('limit')));

        // check if limit is to high if so reduce it to the given limit
        $limit = $limit > self::RESULTS_LIMIT ? self::RESULTS_LIMIT : $limit;
        // if limit is 0 set it to max
        $limit = $limit == 0 ? self::RESULTS_LIMIT : $limit;;

        // decide exactly what the user wants to get
        switch (true) {
            case $id: // id overrides all other parameters
                return new JsonResponse(competition::findOrFail($id));

            case $page: // if page is given (and not 0) assume pagination is desired
                // calculate the total number of pages
                $number_of_pages = ceil(competition::all()->count() * 1.0 / $limit);

                // check if page is larger than $number of pages if so set it to the last page
                $page = $page > $number_of_pages ? $number_of_pages : $page;

                // if a page is given return result with pagination
                $competitions = competition::orderByDesc('changed')->limit($limit)->offset(($page - 1) * $limit)->get();

                // return them as a json
                return new JsonResponse([$number_of_pages, $competitions]);

            default: // only return the first few competitions
                return new JsonResponse(competition::orderByDesc('changed')->limit($limit)->get());
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upsert(Request $request): JsonResponse
    {
        // try to decode request to json (in case of invalid JSON error is thrown)
        $content = json_decode($request->getContent(), false, JSON_THROW_ON_ERROR);

        // make sure content is always an array
        ControllerHelper::makeToArray($content);

        // empty arrays for storing the exceptions and resources produced during the dry run
        $exceptions = [];
        $resources = [];

        // check for errors in provided resources
        foreach ($content as $input_resource) {
            // cast the input (is an object) to an array
            $input_resource = (array) $input_resource;

            /** @var bool variable stores wether a new resource has been created or an existing one gets updated */
            $new_resource = false;

            // if an id has been provided search for the model in the database
            if (!empty($input_resource["id"])) {
                try {
                    $resource = competition::findOrFail($input_resource["id"]);
                } catch (\Throwable $th) {
                    $exceptions[] = $th;
                    continue;
                }

                // check wether the users match, else throw exception
                if ($resource->checkAccess($request->user())) {
                    $exceptions[] = new AccessDeniedException("resource?id=" . strval($resource->id));
                    continue;
                }
            } else {
                $resource = new competition();
                $resource->user_id = $request->user()->getAuthIdentifier();
                $new_resource = true;
            }

            // only mangle with dates if they get touched by the request
            if (isset($input_resource["date_start"]) || isset($input_resource["date_end"])) {
                /**
                 * get the relevant date and store it in an variable
                 * $temp_date_start is set to one of the following variables in the given priority, if the higher prioritized one is null the one following is used (and so on)
                 * 'date provided by the request ("the new on")' > 'date already stored in the competition ("the old one")' > 'the other date (implicitly assuming a single day competition)'
                 * 
                 * Note: - The case in which neither $input_resource["date_start"] nor $input_resource["date_end"] is set/provided, is already taken int account by the enclosing
                 *         if statement (the one, that is one level up from this code)
                 *       - The provided date (if one is provided by the request) get's directly parsed to an Carbon object.
                 *       - The try-catch block is required because the request might provide invalid timestamps that Carbon can't parse (throwing an exception)
                 *       - The variable $input_resource["date_..."] gets reused for the newly determined date
                 */
                try {
                    $input_resource["date_start"] = isset($input_resource["date_start"])
                        ? Carbon::parse($input_resource["date_start"])
                        : (!empty($resource->date_start)
                            ? $resource->date_start
                            : Carbon::parse($input_resource["date_end"]));

                    $input_resource["date_end"] = isset($input_resource["date_end"])
                        ? Carbon::parse($input_resource["date_end"])
                        : (!empty($resource->date_end)
                            ? $resource->date_end
                            : $input_resource["date_start"]); // since one of the both fields must be set we can assume that $temp_date_start contains a date provided by the request
                } catch (\Throwable $th) {
                    $exceptions[] = $th;
                    continue;
                }

                // check that temp_date_end is before temp_date_start, if not set the ending date to the starting on (single day competition)
                if ($input_resource["date_end"]->timestamp < $input_resource["date_start"]->timestamp) {
                    $input_resource["date_end"] = $input_resource["date_start"];
                }
            } elseif ($new_resource) {
                // in case a new record is created dates MUST be provided else throw error
                $exceptions[] = new InvalidArgumentException('A date must be present in a new competition');
                continue;
            }

            // check if required fields for a new resource are present, else throw error
            if ($new_resource) {
                // name and location must be present
                $validator = Validator::make($input_resource, [
                    'name' => 'string',
                    'location' => 'string'
                ]);

                // do the validation in case it failed write exception and continue
                if ($validator->fails()) {
                    $exceptions[] = new InvalidArgumentException($validator->errors()->toJson());
                    continue;
                }
            }

            // validate remaining fields
            $validator = Validator::make($input_resource, [
                'feature_set' => 'nullable|numeric',
                'areas' => 'nullable|numeric|min:1',
                'live' => 'nullable|boolean'
            ]);

            // do the validation in case it failed write exception and continue
            if ($validator->fails()) {
                $exceptions[] = new InvalidArgumentException($validator->errors()->toJson());
                continue;
            }

            // check for duplicates (but only if we want to create a new resource)
            if ($new_resource) {
                // in case a duplicate does exist
                if (competition::query()
                    ->where([
                        // https://stackoverflow.com/questions/2545947/check-overlap-of-date-ranges-in-mysql
                        ['date_end', '>=', $input_resource['date_start']],
                        ['date_start', '<=', $input_resource['date_end']]
                    ])
                    ->where('name', '=', $input_resource['name'])
                    ->where('location', '=', $input_resource['location'])
                    ->exists()
                ) {
                    // duplicate exists so throw error
                    $exceptions[] = 0;
                    continue;
                }
            }

            // should be unnecessary in case of correct implementation
            // if (empty($exceptions))
            //     continue;

            // fill in the data (that has been validated to be correct) into the resource
            $resource->fill($input_resource);

            // add the resource to the list
            $resources[] = $resource;
        }

        // check wether exceptions did occur if so throw an exception
        if (!empty($exceptions))
            throw new MultiException($exceptions);

        // no exceptions did occur we now write the resources to the database
        foreach ($resources as $resource) {
            $resource->save();
            $resource->refresh(); // we want to provide an exact copy whats in the db so we re-hydrate the model with exactly the databases content
        }

        // return the save resources
        return new JsonResponse($resources);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request): JsonResponse
    {
        // TODO add support for implicit identification

        $id = abs(intval($request->input('id')));
        if ($id == null)
            throw new InvalidArgumentException("Parameter 'id' hasn't been provided");

        // try to find competition in the database
        $competition = competition::findOrFail($id);

        // check wether the current user has access
        if (!$competition->checkAccess($request->user()))
            throw new AccessDeniedException("competition?id=" . strval($competition->id));

        // delete record in the database
        $competition->delete();

        return new JsonResponse(["SUCCESS"]);
    }
}
