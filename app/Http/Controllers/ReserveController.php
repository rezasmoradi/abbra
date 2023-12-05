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
            $beginningDatetime = $request->reserved_at;
            $endDatetime = Carbon::parse($request->reserved_at)->addMinutes($serviceTimeCost)->toDateTimeString();


            $existReservation = Reserve::query()
                ->orWhere(function ($q1) use ($beginningDatetime, $endDatetime) {
                    $q1->where('reserved_at', '>=', $beginningDatetime)
                        ->where('reserved_at', '<=', $endDatetime);
                })
                ->orWhere(function ($q2) use ($beginningDatetime, $endDatetime) {
                    $q2->where('reserved_end_time', '>=', $beginningDatetime)
                        ->where('reserved_end_time', '<=', $endDatetime);
                })
                ->orWhere(function ($q3) use ($beginningDatetime, $endDatetime) {
                    $q3->where('reserved_at', '>=', $beginningDatetime)
                        ->where('reserved_end_time', '<=', $endDatetime);
                })
                ->orWhere(function ($q4) use ($beginningDatetime, $endDatetime) {
                    $q4->where('reserved_at', '<=', $beginningDatetime)
                        ->where('reserved_end_time', '>=', $endDatetime);
                })
                ->get();
            $count = $existReservation->toBase()
                ->where('service_worker_id', '=', $request->service_worker_id)
                ->count();


            $reserve = Reserve::query()->where('service_id', $request->service_id)->latest()->first();
            $time = $reserve ? Carbon::parse($reserve->reserved_at) : null;
            $sameDay = $time ? $time->isSameDay($request->reserved_at) : false;
            if ($count > 0) {
                return response()->json(['message' => 'این زمان قبلا رزرو شده است'], Response::HTTP_BAD_REQUEST);
            } else {
                if (!$reserve || !$sameDay || ($sameDay && $time->addMinutes($serviceTimeCost)->greaterThan($request->reserved_at))) {
                    $request->user()->reserves()->create([
                        'service_id' => $request->service_id,
                        'reserved_at' => $request->reserved_at,
                        'service_worker_id' => $request->service_worker_id,
                        'reserved_end_time' => Carbon::parse($request->reserved_at)->addMinutes($serviceTimeCost)->toDateTimeString()
                    ]);

                    return response()->json(['message' => 'رزرو نوبت با موفقیت انجام شد.'], Response::HTTP_CREATED);
                } else {
                    return response()->json(['message' => 'این زمان قبلا رزرو شده است'], Response::HTTP_BAD_REQUEST);
                }
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
