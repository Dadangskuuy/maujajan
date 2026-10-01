<?php

namespace App\Http\Controllers;

use App\Models\foods;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FoodController extends Controller
{
    // Menampilkan halaman daftar seluruh data makanan
    public function index()
    {
        // Mengambil data makanan terbaru dari database dengan pagination (10 data per halaman)
        $foods = foods::latest()->paginate(10);

        // Mengembalikan tampilan halaman index beserta data makanan
        return view('admin.foods.index', compact('foods'));
    }

    // Menampilkan form untuk menambah makanan baru
    public function create()
    {
        // Mengembalikan tampilan form tambah data makanan
        return view('admin.foods.create');
    }

    // Menyimpan data makanan baru yang dikirim dari form
    public function store(Request $request)
    {
        // 1. Validasi input dari form
        $request->validate([
            'name'        => 'required|string|max:255',
            'category'    => 'required|in:Makanan,Minuman,Cemilan',
            'price'       => 'required|numeric|min:0',
            'description' => 'required|string',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // Maksimal 2MB
        ]);

        // 2. Cek apakah ada file gambar yang diunggah
        $imagePath = null;
        if ($request->hasFile('image')) {
            // Simpan gambar ke folder 'storage/app/public/foods'
            $imagePath = $request->file('image')->store('foods', 'public');
        }

        // 3. Simpan data makanan ke database
        foods::create([
            'name'        => $request->name,
            'category'    => $request->category,
            'price'       => $request->price,
            'description' => $request->description,
            'image'       => $imagePath,
        ]);

        // 4. Redirect kembali ke halaman index dengan pesan sukses
        return redirect()->route('foods.index')->with('success', 'Data makanan berhasil ditambahkan!');
    }

    // Menampilkan form untuk mengedit data makanan tertentu
    public function edit(foods $food)
    {
        // Mengembalikan tampilan form edit beserta data makanan yang dipilih
        return view('admin.foods.edit', compact('food'));
    }

    // Memperbarui data makanan di database
    public function update(Request $request, foods $food)
    {
        // 1. Validasi input dari form edit
        $request->validate([
            'name'        => 'required|string|max:255',
            'category'    => 'required|in:Makanan,Minuman,Cemilan',
            'price'       => 'required|numeric|min:0',
            'description' => 'required|string',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // 2. Kelola gambar (jika ada gambar baru yang diunggah)
        $imagePath = $food->image; // Default menggunakan gambar lama
        if ($request->hasFile('image')) {
            // Hapus gambar lama jika file-nya ada di penyimpan lokal
            if ($food->image && Storage::disk('public')->exists($food->image)) {
                Storage::disk('public')->delete($food->image);
            }
            // Simpan gambar baru
            $imagePath = $request->file('image')->store('foods', 'public');
        }

        // 3. Update data makanan di database
        $food->update([
            'name'        => $request->name,
            'category'    => $request->category,
            'price'       => $request->price,
            'description' => $request->description,
            'image'       => $imagePath,
        ]);

        // 4. Redirect kembali ke halaman index dengan pesan sukses
        return redirect()->route('foods.index')->with('success', 'Data makanan berhasil diperbarui!');
    }

    // Menghapus data makanan dari database
    public function destroy(foods $food)
    {
        // 1. Hapus gambar terkait dari direktori penyimpanan jika file-nya ada
        if ($food->image && Storage::disk('public')->exists($food->image)) {
            Storage::disk('public')->delete($food->image);
        }

        // 2. Hapus record data makanan dari database
        $food->delete();

        // 3. Redirect kembali ke halaman index dengan pesan sukses
        return redirect()->route('foods.index')->with('success', 'Data makanan berhasil dihapus!');
    }
}