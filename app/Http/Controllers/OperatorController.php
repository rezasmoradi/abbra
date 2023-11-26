<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateOperatorRequest;
use App\Http\Requests\UpdateOperatorRequest;
use App\Http\Resources\UserResource;
use App\Models\ServiceWorker;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OperatorController extends Controller
{

    public function index()
    {
        $operators = User::query()->where('role', User::ROLE_OPERATOR)->paginate(15);
        return UserResource::collection($operators);
    }

    public function store(CreateOperatorRequest $request)
    {
        try {
            DB::beginTransaction();
            $operator = User::query()->create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'role' => User::ROLE_OPERATOR,
            ]);
            foreach ($request->services as $service) {
                ServiceWorker::query()->create([
                    'service_worker_id' => $operator->id,
                    'service_id' => $service,
                ]);
            }
            DB::commit();
            return response()->json(compact('operator'), Response::HTTP_CREATED);

        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error($exception->getMessage());
            return response()->json(['message', 'خطا در ثبت اپراتور'], Response::HTTP_CREATED);
        }
    }

    public function update(UpdateOperatorRequest $request)
    {
        try {
            DB::beginTransaction();
            $operator = User::query()->findOrFail($request->operator_id)->update($request->all());
            if ($request->services) {
                foreach ($request->services as $service) {
                    ServiceWorker::query()->create([
                        'service_worker_id' => $operator->id,
                        'service_id' => $service,
                    ]);
                }
            }
            DB::commit();
            return response()->json(['message' => 'اپراتور با موفقیت ویرایش شد.'], Response::HTTP_CREATED);

        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json(['message', 'خطا در بروزرسانی اپراتور'], Response::HTTP_CREATED);
        }
    }

    public function delete(Request $request)
    {
        try {
            DB::beginTransaction();

            ServiceWorker::query()->where('service_worker_id', $request->operator_id);
            $user = User::query()->findOrFail($request->operator_id);
            if ($user->isAdmin()) {
                return response()->json(['message' => 'کاربر مدیر قابل حذف نیست.'], Response::HTTP_CREATED);
            } else {
                $user->delete();
            }
            DB::commit();
            return response()->json(['message' => 'اپراتور با موفقیت حذف شد.'], Response::HTTP_CREATED);

        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error($exception);
            return response()->json(['message', 'خطا در حذف اپراتور'], Response::HTTP_CREATED);
        }
    }
}
