<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateReserveRequest;
use App\Http\Requests\GetOperatorsList;
use App\Http\Resources\UserResource;
use App\Models\Reserve;
use App\Models\Service;
use App\Models\ServiceWorker;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ReserveController extends Controller
{
    public function index()
    {
        if (auth()->user()->isAdmin()) {
            return response()->json(['reserves' => Reserve::with(['service', 'customer', 'operator'])->get()]);
        }
        return response()->json(['reserves' => auth()->user()->reserves()->get()]);
    }

    public function create(CreateReserveRequest $request)
    {
        try {
            $serviceTimeCost = Service::query()->findOrFail($request->service_id)->time_cost;
            $reserve = Reserve::query()->where('service_id', $request->service_id)->latest()->first();
            $time = $reserve ? Carbon::parse($reserve->reserved_at) : null;
            $sameDay = $time ? $time->isSameDay($request->reserved_at) : null;
            if (!$reserve || !$sameDay || $time === null || ($sameDay && $time->addMinutes($serviceTimeCost)->greaterThan($request->reserved_at))) {
                $request->user()->reserves()->create([
                    'service_id' => $request->service_id,
                    'reserved_at' => $request->reserved_at,
                    'service_worker_id' => $request->service_worker_id,
                    'reserved_end_time' => Carbon::parse($request->reserved_at)->addMinutes($serviceTimeCost)->toDateTimeString()
                ]);

                return response()->json(['message' => 'رزرو نوبت با موفقیت انجام شد.'], Response::HTTP_CREATED);
            } else {
                return response()->json(['message' => 'این زمان قبلا توسط شخص دیگری رزرو شده است'], Response::HTTP_BAD_REQUEST);
            }

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return response()->json(['message' => 'خطا در رزرو نوبت'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function operators(GetOperatorsList $request)
    {
        $service = Service::query()->findOrFail($request->service_id);
        $reserveDate = Carbon::parse($request->reserved_at)->toDateString();
        $reserveTime = Carbon::parse($request->reserved_at)->toTimeString();
        $endDatetime = Carbon::parse($request->reserved_at)->addMinutes($service->time_cost)->toTimeString();
        $operators = ServiceWorker::query()->select('users.*')
            ->leftJoin('users', 'users.id', '=', 'service_workers.service_worker_id')
            ->leftJoin('reserves', 'reserves.service_worker_id', '=', 'service_workers.service_worker_id')
            ->where('service_workers.service_id', $request->service_id)
            ->whereDate('reserves.reserved_at', '=', $reserveDate)
            ->whereTime('reserves.reserved_at', '>', $endDatetime)
            ->whereTime('reserves.reserved_end_time', '<', $reserveTime)
            ->orWhereDate('reserves.reserved_at', '!=', $reserveDate)->get();

        return UserResource::collection($operators);
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
