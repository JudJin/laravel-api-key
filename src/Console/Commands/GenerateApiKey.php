<?php

namespace Ejarnutowski\LaravelApiKey\Console\Commands;

use Ejarnutowski\LaravelApiKey\Models\ApiKey;
use Illuminate\Console\Command;

class GenerateApiKey extends Command
{
    /**
     * Error messages
     */
    const MESSAGE_ERROR_INVALID_NAME_FORMAT = 'Invalid name.  Must be a lowercase alphabetic characters and hyphens less than 255 characters long.';
    const MESSAGE_ERROR_NAME_ALREADY_USED = 'Name is unavailable.';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apikey:generate {name} {user_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a new API key';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $user_id = $this->argument('user_id') ?? null;

        $error = $this->validateName($name);

        if ($error) {
            $this->error($error);
            return;
        }

        if (isset($user_id)) {

            $userClass = config('auth.providers.users.model');

            if (empty($userClass) || is_null($userClass)) {
                $this->error("User model not found. Please check your config");
                return;
            }

            $user = $userClass::find($user_id);

            if (empty($user)) {
                $this->error(sprintf('User with id %s does not exist', $user_id));
                return;
            }

            if (ApiKey::where(['user_id' => $user_id, 'active' => 1])->exists()) {
                $this->error(sprintf('User with id %s yet has an active key', $user_id));
                return;
            }
        }


        $apiKey = new ApiKey;
        $apiKey->name = $name;
        $apiKey->key = ApiKey::generate();
        $apiKey->user_id = $user_id;
        $apiKey->save();

        $this->info('API key created');
        $this->info('Name: ' . $apiKey->name);
        $this->info('Key: ' . $apiKey->key);
    }

    /**
     * Validate name
     *
     * @param string $name
     * @return string
     */
    protected function validateName($name)
    {
        if (!ApiKey::isValidName($name)) {
            return self::MESSAGE_ERROR_INVALID_NAME_FORMAT;
        }
        if (ApiKey::nameExists($name)) {
            return self::MESSAGE_ERROR_NAME_ALREADY_USED;
        }
        return null;
    }
}
