<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Table;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TableController extends Controller
{
    /**
     * Display a listing of the tables.
     */
    public function index()
    {
        $tables = Table::orderBy('nama_meja', 'asc')->get();
        return response()->json([
            'success' => true,
            'data' => $tables
        ]);
    }

    /**
     * Store a newly created table.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_meja' => 'required|string|max:255',
        ]);

        $table = Table::create([
            'nama_meja' => $request->nama_meja,
            'hash_slug' => Str::random(10),
            'status' => 'available' // default
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Meja berhasil ditambahkan.',
            'data' => $table
        ]);
    }

    /**
     * Update the specified table.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_meja' => 'required|string|max:255',
            'status' => 'required|in:available,occupied',
        ]);

        $table = Table::findOrFail($id);
        
        $table->update([
            'nama_meja' => $request->nama_meja,
            'status' => $request->status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Meja berhasil diperbarui.',
            'data' => $table
        ]);
    }

    /**
     * Remove the specified table.
     */
    public function destroy($id)
    {
        $table = Table::findOrFail($id);
        
        // Cek jika sedang dipakai
        if ($table->status === 'occupied') {
            return response()->json([
                'success' => false,
                'message' => 'Meja sedang dipakai, tidak bisa dihapus.'
            ], 400);
        }

        $table->delete();

        return response()->json([
            'success' => true,
            'message' => 'Meja berhasil dihapus.'
        ]);
    }

    /**
     * Clear the table (set to available).
     */
    public function clear($id)
    {
        $table = Table::findOrFail($id);
        
        $table->update([
            'status' => 'available',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Meja berhasil dikosongkan.',
            'data' => $table
        ]);
    }
}
