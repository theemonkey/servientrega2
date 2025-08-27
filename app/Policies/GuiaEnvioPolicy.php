<?php

namespace App\Policies;

use App\Models\GuiaEnvio;
use App\Models\User;

class GuiaEnvioPolicy
{
    public function view(User $user, GuiaEnvio $guia)
    {
        return $user->id === $guia->user_id;
    }

    public function update(User $user, GuiaEnvio $guia)
    {
        return $user->id === $guia->user_id;
    }

    public function delete(User $user, GuiaEnvio $guia)
    {
        return $user->id === $guia->user_id;
    }
}