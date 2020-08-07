<?php
/**
 * Created by PhpStorm.
 * User: nouks
 * Date: 2/22/20
 * Time: 1:23 PM
 */

namespace App\Services;


use App\CustomObjects\Dtos\RegisterUserDto;
use App\CustomObjects\Dtos\UserResponseDto;
use App\CustomObjects\HttpStatus;
use App\Exceptions\ApiException;
use App\Role;
use App\User;

class UserService
{
    public function register(RegisterUserDto $userDto) {
        $valid = $userDto->validate();
        if ($valid !== null) {
            return $valid;
        } else if (!$this->validateNewUser($userDto->getEmail())) {
            return ApiException::report("email already in use",
                HttpStatus::HTTP_CONFLICT, $userDto->getEndpoint());
        }

        $user = User::create([
            "email" => $userDto->getEmail(),
            "password" => $userDto->getEncryptedPassword(),
            "name" => $userDto->getFullName()
        ]);
        $role = Role::where('name', 'USER')->get()->first();
        if ($role !== []) {
            $user->roles()->attach($role->id);
        }
        // prepare roles array
        $roles = [];
        foreach ($user->roles()->get() as $key => $role) {
            $roles[$key] = $role->name;
        }

        return new UserResponseDto(
            $user->name,
            $user->email,
            $user->updated_at,
            $user->id,
            $roles
        );
    }

    public function validateNewUser($email) {
        $u = User::where("email", $email)->get()->first();
        return $u == null;
    }

    public static function getCurrentAuthUser() {
        $claims = AuthService::getClaims();
        return User::find($claims["userId"]);
    }
}