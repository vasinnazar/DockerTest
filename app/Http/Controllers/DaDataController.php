<?php

namespace App\Http\Controllers;

use App\Facades\DadataFacade;
use App\Http\Requests\Api\DebtorSuggestRequest;
use Illuminate\Http\JsonResponse;

class DaDataController extends Controller
{
    public function suggestAddress(DebtorSuggestRequest $request): JsonResponse
    {
        $dadataResults = DadataFacade::suggest('address', $request->get('address'));
        foreach ($dadataResults as $result) {
            $results[] = [
                'value' => $result['value'] ?? '',
                'unrestricted_value' => $result['unrestricted_value'] ?? '',
                'data' => $result['data'] ?? ''
            ];
        }
        return response()->json($results ?? []);
    }

}