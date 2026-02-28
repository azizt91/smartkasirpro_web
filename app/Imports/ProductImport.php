<?php

namespace App\Imports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ProductImport implements ToModel, WithHeadingRow, WithValidation
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // Skip empty rows
        if (!isset($row['nama_produk'])) {
            return null;
        }

        return new Product([
            'name'              => $row['nama_produk'],
            'barcode'           => $row['barcode'] ?? null,
            'category_id'       => $row['id_kategori'],
            'purchase_price'    => current(explode('.', $row['harga_beli'] ?? 0)), // Ensure Integer
            'selling_price'     => current(explode('.', $row['harga_jual'] ?? 0)),
            'stock'             => $row['stok'] ?? 0,
            'type'              => strtolower($row['tipe_produk'] ?? 'barang'),
            'commission_type'   => isset($row['tipe_komisi']) ? strtolower($row['tipe_komisi']) : null,
            'commission_amount' => $row['nominal_komisi'] ?? 0,
        ]);
    }

    public function rules(): array
    {
        return [
            'nama_produk' => 'required|string|max:255',
            'barcode' => 'nullable|string|unique:products,barcode',
            'id_kategori' => 'required|exists:categories,id',
            'harga_beli' => 'required|numeric|min:0',
            'harga_jual' => 'required|numeric|min:0',
            'stok' => 'required|integer|min:0',
            'tipe_produk' => 'nullable|in:barang,jasa',
            'tipe_komisi' => 'nullable|in:fixed,percentage',
            'nominal_komisi' => 'nullable|numeric|min:0',
        ];
    }
}
