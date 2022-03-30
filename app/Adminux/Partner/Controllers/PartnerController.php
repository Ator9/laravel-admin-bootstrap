<?php

namespace App\Adminux\Partner\Controllers;

use App\Adminux\Partner\Models\Partner;
use App\Adminux\Admin\Controllers\AdminPartnerController;
use App\Adminux\Service\Controllers\ServiceController;
use Illuminate\Http\Request;
use App\Adminux\AdminuxController;
use Yajra\Datatables\Datatables;

class PartnerController extends AdminuxController
{
    public function __construct()
    {
        $this->middleware('adminux_superuser');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Partner $partner)
    {
        if(request()->ajax()) return Datatables::of($partner::query())
            ->addColumn('id2', 'adminux.backend.pages.inc.link_show_link')
            ->addColumn('active2', 'adminux.backend.pages.inc.status')
            ->rawColumns(['id2', 'active2'])
            ->toJson();

        return view('adminux.backend.pages.index')->withDatatables([
            'order' => '[[ 1, "asc" ]]',
            'thead' => '<th style="min-width:30px">ID</th>
                        <th class="w-75">Partner</th>
                        <th style="min-width:60px">Active</th>
                        <th style="min-width:120px">Created At</th>',

            'columns' => '{ data: "id2", name: "id", className: "text-center" },
                          { data: "partner", name: "partner" },
                          { data: "active2", name: "active", className: "text-center" },
                          { data: "created_at", name: "created_at", className: "text-center" }'
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Partner $partner)
    {
        return parent::createView($partner);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Partner $partner)
    {
        $request->validate([
            'partner' => 'required|unique:'.$partner->getTable(),
            'language_id' => 'required',
            'active' => 'in:Y,""',
        ]);

        if(!$request->filled('active')) $request->merge(['active' => 'N']);

        $partner = $partner->create($request->all());

        return parent::saveRedirect($partner);
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Partner $partner)
    {
        if(request()->ajax()) {
            if(request()->table == 'admin_partner') return (new AdminPartnerController)->getIndex($partner);
            // elseif(request()->table == 'services') return (new ServiceController)->getIndex($partner);
        }

        return view('adminux.backend.pages.show')->withModel($partner)->withRelations([
            (new AdminPartnerController)->getIndex($partner),
            // (new ServiceController)->getIndex($partner)
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Partner $partner)
    {
        return parent::editView($partner);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Partner $partner)
    {
        $request->validate([
            'partner' => 'required|unique:'.$partner->getTable().',partner,'.$partner->id,
            'language_id' => 'required',
            'active' => 'in:Y,""',
        ]);

        if(!$request->filled('active')) $request->merge(['active' => 'N']);

        $partner->update($request->all());

        return parent::saveRedirect($partner);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Partner $partner)
    {
        return parent::destroyRedirect($partner);
    }

    /**
     * Build Blade edit & create form fields
     *
     * @return Array
     */
    public function getFields(Partner $partner)
    {
        $form = new \App\Adminux\Form($partner);
        return [
            $form->display([ 'label' => 'ID' ]),
            $form->text([ 'label' => 'Partner' ]),
            $form->select([ 'label' => 'Language' ]),
            $form->moduleConfig([ 'label' => 'Module Config', 'path' => 'partners' ]),
            $form->switch([ 'label' => 'Active' ]),
        ];
    }
}
