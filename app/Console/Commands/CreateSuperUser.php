<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class CreateSuperUser extends Command
{
    protected $signature = 'hrms-lite:create-super-user
                            {name : Display name for the first super user}
                            {email : Unique login email}';

    protected $description = 'Create the first super user interactively (no hardcoded credentials)';

    public function handle(): int
    {
        if (User::query()->where('role', Role::SuperUser)->exists()) {
            $this->error('A super user already exists. Refusing to create another via bootstrap.');

            return self::FAILURE;
        }

        $input = [
            'name' => trim((string) $this->argument('name')),
            'email' => Str::lower(trim((string) $this->argument('email'))),
        ];

        $password = (string) $this->secret('Password');
        $confirmation = (string) $this->secret('Confirm password');

        $validator = Validator::make(
            [
                ...$input,
                'password' => $password,
                'password_confirmation' => $confirmation,
            ],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'confirmed', Password::defaults()],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        User::query()->create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $password,
            'role' => Role::SuperUser,
            'must_change_password' => false,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->info('Super user created. Sign in at /login. The password was not written to logs.');

        return self::SUCCESS;
    }
}
