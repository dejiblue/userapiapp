<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Class UserController
 * @package App\Http\Controllers\API\V1
 */
class UserController extends Controller
{
    /**
     * @var string
     */
    private $user_url    = 'https://pipl.ir/v1/getPerson';

    /**
     * @var string
     */
    private $cartoon_avatar = 'https://robohash.org/';

    /**
     * @var string
     */
    private $image_avatar = 'https://i.pravatar.cc/300';

    /**
     * Returns the API version
     * This is excluded from the route Middleware
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        return response()->json(['User API Version' => 'V1']);
    }

    /**
     * Get all users and limit the amount of records returned
     *
     * @param null $limit
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUsers($limit = null)
    {
        $users = User::orderBy('created_at', 'DESC');

        if($limit) {
            $users->take($limit);
        }

        $users = $users->get();

        if($users) {
            return response()->json([
                'success' => true,
                'message' => 'User list',
                'data' => $users
            ], 200);
        }
        return response()->json(['success' => false]);
    }

    /**
     * Get a single user record
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUser($id)
    {
        $user = User::findorFail($id);
        if($user) {
            $user->avatar = request()->getSchemeAndHttpHost().'/images/v1/'.$user->avatar;
            return response()->json([
                'success' => true,
                'message' => 'User retrieved successfully',
                'data' => $user
            ], 200);
        }
        return response()->json(['success' => false]);
    }

    /**
     * Generate new user record and avatar from external source
     * Results are saved to DB
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function createUser()
    {
        $userData = $this->curlCall(true);

        if($userData) {
            $onlineInfo = $userData->person->online_info;
            $personal = $userData->person->personal;

            try{
                $userModel = new User();
                $userModel->username = $onlineInfo->username;
                $userModel->name = $personal->name;
                $userModel->last_name = $personal->last_name;
                $userModel->father_name = $personal->father_name;
                $userModel->email = $onlineInfo->email;
                $userModel->password = bcrypt($onlineInfo->password);
                $userModel->gender = $personal->gender;
                $userModel->eye_color = $personal->eye_color;
                $userModel->age = $personal->age;
                $userModel->height = $personal->height;
                $userModel->weight = $personal->weight;
                $userModel->blood = $personal->blood;
                $userModel->cellphone = $personal->cellphone;
                $userModel->city = $personal->city;
                $userModel->country = $personal->country;
                $userModel->religion = $personal->religion;
                $userModel->system_id = $personal->system_id;

                $fileName = $userModel->name.'-'.time().'.png';
                $uploadDir = public_path('images').'/v1/'.$fileName;
                $userAvatar = $this->curlCall(false, $this->cartoon_avatar.$personal->name);
                $fp = fopen($uploadDir,'w');
                fwrite($fp, $userAvatar);
                fclose($fp);

                $userModel->avatar = $fileName;
                if($userModel->save()) {
                    $userModel->avatar = request()->getSchemeAndHttpHost().'/images/v1/'.$userModel->avatar;
                    return response()->json([
                        'success' => true,
                        'message' => 'user created',
                        'data' => $userModel
                    ], 200);
                }
            } catch(\Exception $e) {
                Log::error(__METHOD__.': '.$e->getMessage());
            }
        }
        return response()->json(['success' => false]);

    }

    /**
     * Get a user's avatar url
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserAvatar($id)
    {
        $user = User::findorFail($id);
        if($user) {
            $image_path = request()->getSchemeAndHttpHost().'/images/v1/'.$user->avatar;
            return response()->json([
                'success' => true,
                'message' => 'User Avatar retrieved successfully',
                'data' => $image_path
            ], 200);
        }
        return response()->json(['success' => false]);
    }

    /**
     * Generate some basic user statistics
     * using simple queries. for complex reports, a more comprehensive and performance centric query will be required
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserStats()
    {
        $average_age = DB::table('users')
            ->select(DB::raw('round(AVG(age),0) as average_age'),
                     DB::raw('count(*) as total_users')
            )
            ->get();

        $users_by_gender = DB::table('users')
            ->select('gender', DB::raw('count(*) as total'))
            ->groupBy('gender')
            ->get();

        $users_by_country = DB::table('users')
            ->select('country', DB::raw('count(*) as total'))
            ->groupBy('country')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'User stats generated successfully',
            'data' => [
                'total_users' => $average_age[0]->total_users,
                'average_age' => $average_age[0]->average_age,
                'users_by_gender' => $users_by_gender,
                'users_by_country' => $users_by_country
            ]
        ], 200);
    }

    /**
     * @param $isPerson
     * @param null $avatarUrl
     * @return bool|mixed|string
     */
    private function curlCall($isPerson, $avatarUrl = null)
    {
        try {
            $url = $isPerson ? $this->user_url : $avatarUrl;
            $inc_curl = curl_init();
            curl_setopt($inc_curl, CURLOPT_URL, $url);
            curl_setopt($inc_curl, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($inc_curl);
            curl_close($inc_curl);
            if($isPerson) {
                return json_decode($result);
            }
            return $result;
        } catch(\Exception $e) {
            Log::error(__METHOD__.': '.$e->getMessage());
        }
        return false;
    }
}

