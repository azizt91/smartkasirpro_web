<?php

namespace App\Http\Controllers;

use App\Models\Table;
use Illuminate\Http\Request;

class TableController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tables = Table::orderBy('id')->paginate(20);
        return view('tables.index', compact('tables'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_meja' => 'required|string|max:255',
        ]);

        Table::create([
            'nama_meja' => $request->nama_meja,
            'status' => 'available',
        ]);

        return back()->with('success', 'Meja berhasil ditambahkan.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Table $table)
    {
        $request->validate([
            'nama_meja' => 'required|string|max:255',
            'status' => 'required|in:available,occupied',
        ]);

        $table->update([
            'nama_meja' => $request->nama_meja,
            'status' => $request->status,
        ]);

        return back()->with('success', 'Meja berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Table $table)
    {
        // Don't delete if there are active transactions associated with it
        if ($table->status === 'occupied') {
            return back()->with('error', 'Meja sedang digunakan, tidak dapat dihapus.');
        }

        $table->delete();
        return back()->with('success', 'Meja berhasil dihapus.');
    }

    /**
     * Clear the table (set status to available).
     */
    public function clear(Table $table)
    {
        $table->update(['status' => 'available']);
        return back()->with('success', "Meja {$table->nama_meja} berhasil dibersihkan dan siap digunakan.");
    }

    /**
     * Generate / Show QR Code for the specific table.
     */
    public function qrCode(Table $table)
    {
        // Generate the Public Order URL for this table
        $orderUrl = url('/order/' . $table->hash_slug);
        
        // We use an external API to generate the QR Code for simplicity
        // The view will render instructions to print this QR
        return view('tables.qrcode', compact('table', 'orderUrl'));
    }
}
