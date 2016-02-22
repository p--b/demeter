<?php

namespace Demeter\Http\Middleware;

use Closure;
use Illuminate\Http\Response;
use Demeter\ApiKey;

class Authenticate
{
    protected $apiKey;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $request->merge(['apiKey' => $this]);
        do
        {
            if (!($auth = $request->header('Authorization')))
                break;

            $tokens = explode(' ', $auth);
            if ($tokens[0] !== 'Demeter')
                break;

            try
            {
                $this->apiKey = ApiKey::where('key', $tokens[1])->firstOrFail();
            }
            catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e)
            {
                break;
            }

            return $next($request);
        } while (FALSE);

        return (new Response(NULL, 401))
                ->header('WWW-Authenticate', 'Demeter realm="Demeter"');
    }

    public function hasRole($role)
    {
        return $this->apiKey->role == $role;
    }

    public function getId()
    {
        return $this->apiKey->id;
    }
}
