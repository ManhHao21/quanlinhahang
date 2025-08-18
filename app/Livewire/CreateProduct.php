<?php

namespace App\Livewire;

use App\Models\Menu;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

class CreateProduct extends Component
{
    use WithFileUploads;
    public $search = '';
    public $menuId = null;
    public $name;
    public $price;
    public $description;
    public $image;
    public $imagePath;

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:1000',
            // 'image' => $this->menuId ? 'nullable|image|max:2048' : 'required|image|max:2048',
        ]);

        if ($this->menuId) {
            // Update
            $menu = Menu::findOrFail($this->menuId);
            $menu->name = $this->name;
            $menu->price = $this->price;
            $menu->description = $this->description;

            if ($this->image) {
                $path = $this->image->store('images', 'public');
                $menu->image = $path;
            }

            $menu->save();
            session()->flash('message', 'Cập nhật món ăn thành công.');
        } else {
            // Create
            $path = $this->image ? $this->image->store('images', 'public') : null;

            Menu::create([
                'name' => $this->name,
                'price' => $this->price,
                'description' => $this->description,
                'image' => $path,
            ]);

            session()->flash('message', 'Thêm món ăn thành công.');
        }

        $this->resetForm();
    }

    public function edit($id)
    {
        $menu = Menu::findOrFail($id);
        $this->menuId = $menu->id;
        $this->name = $menu->name;
        $this->price = $menu->price;
        $this->description = $menu->description;
        $this->imagePath = $menu->image;
    }

    public function resetForm()
    {
        $this->menuId = null;
        $this->name = '';
        $this->price = '';
        $this->description = '';
        $this->image = null;
        $this->imagePath = null;
    }
    public function destroy($id)
    {
        $menu = Menu::findOrFail($id);

        // Xóa ảnh nếu có
        if ($menu->image && Storage::disk('public')->exists($menu->image)) {
            Storage::disk('public')->delete($menu->image);
        }

        $menu->delete();

        // Cập nhật lại danh sách sau khi xóa
        $this->menus = Menu::all();

        session()->flash('message', 'Xóa món thành công.');
    }
    public function render()
    {
        $menus = Menu::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->orderBy('name')->paginate(10)->withQueryString();
        return view('livewire.create-product', compact('menus'));
    }
}
