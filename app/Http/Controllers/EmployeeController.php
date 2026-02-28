<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
class EmployeeController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $employees = Employee::orderBy('name')->paginate(10);
        return view('employees.index', compact('employees'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('employees.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        Employee::create($request->all());

        return redirect()->route('employees.index')
            ->with('success', 'Data ' . (\App\Models\Setting::getStoreSettings()->employee_label ?? 'Pegawai') . ' Jasa berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Employee $employee)
    {
        return view('employees.edit', compact('employee'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Employee $employee)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        $employee->update($request->all());

        return redirect()->route('employees.index')
            ->with('success', 'Data ' . (\App\Models\Setting::getStoreSettings()->employee_label ?? 'Pegawai') . ' Jasa berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee)
    {
        try {
            $employee->delete();
            return redirect()->route('employees.index')->with('success', 'Data ' . (\App\Models\Setting::getStoreSettings()->employee_label ?? 'Pegawai') . ' Jasa berhasil dihapus.');
        } catch (\Illuminate\Database\QueryException $e) {
            // Check if the exception is an integrity constraint violation (e.g. foreign key constraint)
            if ($e->getCode() == 23000) {
                return redirect()->back()->with('error', 'Tidak dapat menghapus ' . strtolower(\App\Models\Setting::getStoreSettings()->employee_label ?? 'pegawai') . ' ini karena sudah memiliki riwayat pengerjaan jasa.');
            }
            throw $e;
        }
    }
}
