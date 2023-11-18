<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateReserveRequest;
use App\Models\Reserve;
use App\Models\Service;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class ReserveController extends Controller
{
    public function index()
    {
        if (auth()->user()->isAdmin()) {
            return response()->json(['reserves' => Reserve::all()]);
        }
        return response()->json(['reserves' => auth()->user()->reserves]);
    }

    public function create(CreateReserveRequest $request)
    {
        try {
            $serviceTimeCost = Service::query()->findOrFail($request->service_id)->time_cost;
            $reserve = Reserve::query()->where('service_id', $request->service_id)->latest()->first();
            if (!$reserve || Carbon::parse($reserve->reserved_at)->addMinutes($serviceTimeCost)->greaterThan($request->reserved_at)) {
                $request->user()->reserves()->create([
                    'service_id' => $request->service_id,
                    'reserved_at' => $request->reserved_at
                ]);

                return response()->json(['message' => 'رزرو نوبت با موفقیت انجام شد.'], Response::HTTP_CREATED);
            } else {
                return response()->json(['message' => 'این زمان قبلا توسط شخص دیگری رزرو شده است'], Response::HTTP_BAD_REQUEST);
            }

        } catch (\Exception $exception) {
            return response()->json(['message' => 'خطا در رزرو نوبت'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete(int $id)
    {
        if ($reserve = Reserve::query()->find($id)) {
            $reserve->delete();
            return response()->json(['message' => 'رزرو نوبت با موفقیت حذف شد'], Response::HTTP_OK);
        } else {
            return response()->json(['message' => 'رزرو نوبت با این اطلاعات یافت نشد'], Response::HTTP_NOT_FOUND);
        }
    }
}
