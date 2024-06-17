<?php

namespace Database\Seeders;

use App\Models\Calidad;
use App\Models\ItemPresupuestario;
use App\Models\ItemPresupuestarioUser;
use App\Models\Ley;
use Illuminate\Database\Seeder;

class ItemPresupuestarioUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $item = new ItemPresupuestarioUser();
        $item->item_presupuestario_id   = $this->getItem('210100400601')->id;
        $item->calidad_id               = $this->getCalidad('TITULARES')->id;
        $item->ley_id                   = $this->getLey('15076')->id;
        $item->save();

        $item = new ItemPresupuestarioUser();
        $item->item_presupuestario_id   = $this->getItem('210100400602')->id;
        $item->calidad_id               = $this->getCalidad('TITULARES')->id;
        $item->ley_id                   = $this->getLey('18834')->id;
        $item->save();

        $item = new ItemPresupuestarioUser();
        $item->item_presupuestario_id   = $this->getItem('210100400603')->id;
        $item->calidad_id               = $this->getCalidad('TITULARES')->id;
        $item->ley_id                   = $this->getLey('19664')->id;
        $item->save();

        $item = new ItemPresupuestarioUser();
        $item->item_presupuestario_id   = $this->getItem('210200400601')->id;
        $item->calidad_id               = $this->getCalidad('CONTRATADOS')->id;
        $item->ley_id                   = $this->getLey('15076')->id;
        $item->save();

        $item = new ItemPresupuestarioUser();
        $item->item_presupuestario_id   = $this->getItem('210200400602')->id;
        $item->calidad_id               = $this->getCalidad('CONTRATADOS')->id;
        $item->ley_id                   = $this->getLey('18834')->id;
        $item->save();

        $item = new ItemPresupuestarioUser();
        $item->item_presupuestario_id   = $this->getItem('210200400603')->id;
        $item->calidad_id               = $this->getCalidad('CONTRATADOS')->id;
        $item->ley_id                   = $this->getLey('19664')->id;
        $item->save();

        $item = new ItemPresupuestarioUser();
        $item->item_presupuestario_id   = $this->getItem('210300100105')->id;
        $item->calidad_id               = $this->getCalidad('HONORARIOS')->id;
        $item->ley_id                   = NULL;
        $item->save();
    }

    public function getItem($nom)
    {
        $item = ItemPresupuestario::where('nombre', $nom)->first();
        return $item;
    }

    public function getCalidad($nom)
    {
        $calidad = Calidad::where('nombre', $nom)->first();
        return $calidad;
    }

    public function getLey($nom)
    {
        $ley = Ley::where('nombre', $nom)->first();
        return $ley;
    }
}
