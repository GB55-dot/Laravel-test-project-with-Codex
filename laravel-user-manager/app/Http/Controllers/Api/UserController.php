<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexUsersRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    /**
     * GET /api/users
     *
     * Return a cached, newest-first page of users. Selecting only public
     * columns reduces transferred data and guarantees password hashes are not
     * loaded unnecessarily.
     */
    public function index(IndexUsersRequest $request): AnonymousResourceCollection
    {
        $page = $request->integer('page', 1);
        $perPage = $request->integer('per_page', config('users.default_per_page'));
        $version = Cache::get('users.cache.version', 'initial');
        $cacheKey = "users.index.{$version}.page.{$page}.per-page.{$perPage}";

        $users = Cache::remember(
            $cacheKey,
            now()->addSeconds(config('users.cache_ttl')),
            fn () => User::query()
                ->select([
                    'id',
                    'name',
                    'email',
                    'email_verified_at',
                    'created_at',
                    'updated_at',
                ])
                ->latest('id')
                ->paginate($perPage, ['*'], 'page', $page),
        );

        return UserResource::collection($users);
    }

    /**
     * POST /api/users
     *
     * Validate and create a user inside a transaction. The password is stored
     * only after one-way hashing and is never returned by UserResource.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = DB::transaction(fn (): User => User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]));

        Log::info('User created.', [
            'actor_id' => $request->user()->getAuthIdentifier(),
            'user_id' => $user->getKey(),
        ]);

        return (new UserResource($user))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * GET /api/users/{user}
     *
     * Return one route-model-bound user or Laravel's standardized JSON 404.
     */
    public function show(User $user): UserResource
    {
        return new UserResource($user);
    }

    /**
     * PUT/PATCH /api/users/{user}
     *
     * Update validated fields atomically. Omitting the password preserves the
     * current hash; sending one replaces it with a newly generated hash.
     */
    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $data = $request->safe()->except(['password', 'password_confirmation']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->string('password')->toString());
        }

        DB::transaction(fn () => $user->update($data));

        Log::info('User updated.', [
            'actor_id' => $request->user()->getAuthIdentifier(),
            'user_id' => $user->getKey(),
        ]);

        return new UserResource($user->refresh());
    }

    /**
     * DELETE /api/users/{user}
     *
     * Delete the selected user atomically and return an empty 204 response.
     */
    public function destroy(IndexUsersRequest $request, User $user): Response
    {
        $userId = $user->getKey();

        DB::transaction(fn () => $user->delete());

        Log::notice('User deleted.', [
            'actor_id' => $request->user()->getAuthIdentifier(),
            'user_id' => $userId,
        ]);

        return response()->noContent();
    }
}
