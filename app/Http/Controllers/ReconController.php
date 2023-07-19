<?php


namespace App\Http\Controllers;


use App\Classes\DashboardUtils;
use App\Classes\TransactionReconService;
use App\Exceptions\UnAuthorizedRequest;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReconController extends Controller
{
    private $transactionReconService;

    public function __construct(TransactionReconService $transactionReconService)
    {

        $this->transactionReconService = $transactionReconService;
    }

    /**
     * @throws UnAuthorizedRequest
     */
    public function transactionRecon(Request $request) {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return response()->json(['status' => false, 'message' => $error])->setStatusCode(400);
        }

        $merchantId = DashboardUtils::merchantId();
        $transactionDetail = (new Transaction())->getTransactionDetailsForRecon($merchantId, $request->transaction_id);
        if(isset($transactionDetail)) {
            $reconDetail = $this->transactionReconService->getTransactionFromBank($merchantId, $request->transaction_id, $transactionDetail->txn_token);
            if(isset($reconDetail)) {
                if($reconDetail->status) {
                    if($reconDetail->data->is_mismatch) {
                        return DashboardUtils::errorResponse($reconDetail->message);
                    }
                    $data = base64_encode(view("components.widget.transaction-recon")->with("data", $reconDetail->data)->render());
                    return DashboardUtils::successResponse("Transaction Detail not found", $data);
                }
            }
        }
        return DashboardUtils::errorResponse("Transaction Detail not found");
    }

    /**
     * @throws UnAuthorizedRequest
     */
    public function transactionReconAction(Request $request) {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|string',
            'action' => 'required|string|in:accept,refund',
        ]);
        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return response()->json(['status' => false, 'message' => $error])->setStatusCode(400);
        }

        $merchantId = DashboardUtils::merchantId();
        $transactionDetail = (new Transaction())->getTransactionDetailsForRecon($merchantId, $request->transaction_id);

        if(isset($transactionDetail)) {
            $reconDetail = $this->transactionReconService->reconTransactionFromBank($merchantId, $request->transaction_id, $transactionDetail->txn_token, $request->action);
            if(isset($reconDetail)) {
                if($reconDetail->status) {
                    DashboardUtils::LogDB("PAYIN_RECON", "Payin Recon Action Received PayIn: {$request->transaction_id}, Action: {$request->action}");
                    return DashboardUtils::successResponse($reconDetail->message);
                }
                return DashboardUtils::errorResponse($reconDetail->message);
            }
        }
        return DashboardUtils::errorResponse("Transaction Detail not found");
    }

}
