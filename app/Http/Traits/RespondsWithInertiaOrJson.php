<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

trait RespondsWithInertiaOrJson
{
    /**
     * Determine if the request expects a JSON response.
     */
    protected function wantsJson(Request $request): bool
    {
        return $request->wantsJson() || $request->is('api/*');
    }

    /**
     * Return either an Inertia page or JSON resource based on request type.
     */
    protected function respondWith(
        Request $request,
        string $component,
        array $props,
        JsonResource|ResourceCollection|array|null $resource = null
    ): InertiaResponse|JsonResponse {
        if ($this->wantsJson($request)) {
            return response()->json([
                'data' => $resource ?? $props,
            ]);
        }

        return Inertia::render($component, $props);
    }

    /**
     * Return either an Inertia page or paginated JSON resource.
     */
    protected function respondWithPaginated(
        Request $request,
        string $component,
        array $props,
        ResourceCollection $collection,
        string $dataKey = 'data'
    ): InertiaResponse|JsonResponse {
        if ($this->wantsJson($request)) {
            return response()->json($collection->response()->getData(true));
        }

        return Inertia::render($component, $props);
    }

    /**
     * Return a success response (redirect for web, JSON for API).
     */
    protected function respondWithSuccess(
        Request $request,
        string $route,
        array $routeParams = [],
        string $message = 'Operation completed successfully.',
        array $data = [],
        int $status = 200
    ): RedirectResponse|JsonResponse {
        if ($this->wantsJson($request)) {
            return response()->json([
                'message' => $message,
                'data' => $data,
            ], $status);
        }

        return redirect()->route($route, $routeParams)
            ->with('success', $message);
    }

    /**
     * Return a created response (redirect for web, 201 JSON for API).
     */
    protected function respondWithCreated(
        Request $request,
        string $route,
        array $routeParams = [],
        string $message = 'Resource created successfully.',
        JsonResource|array $data = []
    ): RedirectResponse|JsonResponse {
        if ($this->wantsJson($request)) {
            $responseData = $data instanceof JsonResource ? $data->resolve() : $data;

            return response()->json([
                'message' => $message,
                'data' => $responseData,
            ], 201);
        }

        return redirect()->route($route, $routeParams)
            ->with('success', $message);
    }

    /**
     * Return an error response (redirect back for web, JSON for API).
     */
    protected function respondWithError(
        Request $request,
        string $message,
        array $errors = [],
        int $status = 422
    ): RedirectResponse|JsonResponse {
        if ($this->wantsJson($request)) {
            $response = ['message' => $message];
            if (! empty($errors)) {
                $response['errors'] = $errors;
            }

            return response()->json($response, $status);
        }

        return redirect()->back()
            ->withInput()
            ->with('error', ['message' => $message, 'errors' => $errors]);
    }

    /**
     * Return a deleted response (redirect for web, 204 or JSON for API).
     */
    protected function respondWithDeleted(
        Request $request,
        string $route,
        array $routeParams = [],
        string $message = 'Resource deleted successfully.'
    ): RedirectResponse|JsonResponse {
        if ($this->wantsJson($request)) {
            return response()->json([
                'message' => $message,
            ]);
        }

        return redirect()->route($route, $routeParams)
            ->with('success', $message);
    }
}
