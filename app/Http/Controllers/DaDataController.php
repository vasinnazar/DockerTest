<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\DebtorSuggestRequest;
use Dadata\DadataClient;
use Illuminate\Http\JsonResponse;

class DaDataController extends Controller
{
    public function suggestAddress(DebtorSuggestRequest $request): JsonResponse
    {
        $daData = new DadataClient(env('DADATA_API_KEY'), env('DADATA_API_SECRET'));
        $dadataResults = $daData->suggest('address', $request->address);
        foreach ($dadataResults as $result) {
            $results[] = [
                'value' => $result['value'] ?? '',
                'unrestricted_value' => $result['unrestricted_value'] ?? ''
            ];
        }
        return response()->json($results ?? []);
    }

}