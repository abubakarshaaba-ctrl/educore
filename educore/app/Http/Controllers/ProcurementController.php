<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProcurementController extends Controller
{
    public function index()
    {
        $vendors = Vendor::orderBy('name')->get();
        $orders = PurchaseOrder::with(['vendor', 'items', 'requester'])->latest()->paginate(20);

        return view('procurement.index', compact('vendors', 'orders'));
    }

    public function storeVendor(Request $request)
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:150'],
            'contact_person' => ['nullable', 'string', 'max:120'],
            'phone'          => ['nullable', 'string', 'max:30'],
            'email'          => ['nullable', 'email', 'max:150'],
            'address'        => ['nullable', 'string', 'max:255'],
            'category'       => ['nullable', 'string', 'max:100'],
        ]);
        $data['is_active'] = true;

        Vendor::create($data);

        return back()->with('success', 'Vendor added.');
    }

    public function storeOrder(Request $request)
    {
        $data = $request->validate([
            'vendor_id'   => ['required', 'exists:vendors,id'],
            'notes'       => ['nullable', 'string'],
            'expected_delivery_date' => ['nullable', 'date'],
            'items'       => ['required', 'array', 'min:1'],
            'items.*.item_name'  => ['required', 'string', 'max:200'],
            'items.*.quantity'   => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $order = DB::transaction(function () use ($data) {
            $order = PurchaseOrder::create([
                'vendor_id'   => $data['vendor_id'],
                'po_number'   => 'PO-' . now()->format('Ym') . '-' . strtoupper(Str::random(5)),
                'status'      => 'pending_approval',
                'notes'       => $data['notes'] ?? null,
                'requested_by' => auth()->id(),
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'total_amount' => 0,
            ]);

            $total = 0;
            foreach ($data['items'] as $item) {
                $lineTotal = $item['quantity'] * $item['unit_price'];
                $total += $lineTotal;
                PurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'item_name'  => $item['item_name'],
                    'quantity'   => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $lineTotal,
                ]);
            }
            $order->update(['total_amount' => $total]);

            return $order;
        });

        return back()->with('success', "Purchase order {$order->po_number} created.");
    }

    public function approve(PurchaseOrder $order)
    {
        $order->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);
        return back()->with('success', 'Purchase order approved.');
    }

    public function markReceived(PurchaseOrder $order)
    {
        $order->update(['status' => 'received']);
        return back()->with('success', 'Purchase order marked as received.');
    }

    public function cancel(PurchaseOrder $order)
    {
        $order->update(['status' => 'cancelled']);
        return back()->with('success', 'Purchase order cancelled.');
    }
}
