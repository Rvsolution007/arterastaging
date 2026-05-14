<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClientError;

class ClientErrorController extends Controller
{
    public function index()
    {
        $errors = ClientError::with('user')->orderBy('created_at', 'desc')->paginate(20);
        return view('admin.client_errors.index', compact('errors'));
    }

    public function destroy($id)
    {
        $error = ClientError::findOrFail($id);
        $error->delete();
        return redirect()->back()->with('success', 'Error report deleted successfully.');
    }

    public function updateStatus(Request $request, $id)
    {
        $error = ClientError::findOrFail($id);
        $error->update(['status' => $request->status]);
        return response()->json(['status' => 'success', 'message' => 'Error status updated to ' . $request->status]);
    }

    public function bulkUpdateStatus(Request $request)
    {
        $ids = $request->ids;
        $status = $request->status;
        if (is_array($ids) && count($ids) > 0) {
            ClientError::whereIn('id', $ids)->update(['status' => $status]);
            return response()->json(['status' => 'success', 'message' => 'Selected error reports marked as ' . $status]);
        }
        return response()->json(['status' => 'error', 'message' => 'No items selected.'], 400);
    }

    public function bulk_destroy(Request $request)
    {
        $ids = $request->ids;
        if (is_array($ids) && count($ids) > 0) {
            ClientError::whereIn('id', $ids)->delete();
            return response()->json(['status' => 'success', 'message' => 'Selected error reports deleted successfully.']);
        }
        return response()->json(['status' => 'error', 'message' => 'No items selected.'], 400);
    }
}
