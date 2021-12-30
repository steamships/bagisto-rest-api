<?php

namespace Webkul\RestApi\Http\Controllers\V1\Admin\Catalog;

use Illuminate\Http\Request;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Repositories\AttributeRepository;

class AttributeFamilyController extends CatalogController
{
    /**
     * Attribute family repository instance.
     *
     * @var \Webkul\Attribute\Repositories\AttributeFamilyRepository
     */
    protected $attributeFamilyRepository;

    /**
     * Attribute repository instance.
     *
     * @var \Webkul\Attribute\Repositories\AttributeRepository
     */
    protected $attributeRepository;

    /**
     * Create a new controller instance.
     *
     * @param  \Webkul\Attribute\Repositories\AttributeFamilyRepository  $attributeFamilyRepository
     * @param  \Webkul\Attribute\Repositories\AttributeRepository  $attributeRepository
     * @return void
     */
    public function __construct(
        AttributeFamilyRepository $attributeFamilyRepository,
        AttributeRepository $attributeRepository
    ) {
        $this->attributeFamilyRepository = $attributeFamilyRepository;

        $this->attributeRepository = $attributeRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $attributeFamilies = $this->attributeFamilyRepository->all();

        return response([
            'data' => $attributeFamilies,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate(request(), [
            'code' => ['required', 'unique:attribute_families,code', new \Webkul\Core\Contracts\Validations\Code],
            'name' => 'required',
        ]);

        $attributeFamily = $this->attributeFamilyRepository->create(request()->all());

        return response([
            'data'    => $attributeFamily,
            'message' => __('admin::app.response.create-success', ['name' => 'Family']),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function show(Request $request, $id)
    {
        $attributeFamily = $this->attributeFamilyRepository->with(['attribute_groups.custom_attributes'])->findOrFail($id, ['*']);

        $custom_attributes = $this->attributeRepository->all(['id', 'code', 'admin_name', 'type']);

        return response([
            'data' => compact('attributeFamily', 'custom_attributes'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate(request(), [
            'code' => ['required', 'unique:attribute_families,code,' . $id, new \Webkul\Core\Contracts\Validations\Code],
            'name' => 'required',
        ]);

        $attributeFamily = $this->attributeFamilyRepository->update(request()->all(), $id);

        return response([
            'data'    => $attributeFamily,
            'message' => __('admin::app.response.update-success', ['name' => 'Family']),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $attributeFamily = $this->attributeFamilyRepository->findOrFail($id);

        if ($this->attributeFamilyRepository->count() == 1) {
            return response([
                'message' => __('admin::app.response.last-delete-error', ['name' => 'Family']),
            ], 400);

        } elseif ($attributeFamily->products()->count()) {
            return response([
                'message' => __('admin::app.response.attribute-product-error', ['name' => 'Attribute family']),
            ], 400);
        } else {
            try {
                $this->attributeFamilyRepository->delete($id);

                return response([
                    'message' => __('admin::app.response.delete-success', ['name' => 'Family']),
                ]);
            } catch (\Exception $e) {
                return response([
                    'message' => __('admin::app.response.delete-failed', ['name' => 'Family']),
                ], 500);
            }
        }
    }

    /**
     * Remove the specified resources from database
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function massDestroy(Request $request)
    {
        $suppressFlash = false;

        if (request()->isMethod('delete')) {
            $indexes = explode(',', request()->input('indexes'));

            foreach ($indexes as $key => $value) {
                try {
                    $this->attributeFamilyRepository->delete($value);
                } catch (\Exception $e) {
                    $suppressFlash = true;

                    continue;
                }
            }

            if (! $suppressFlash) {
                return response([
                    'message' => __('admin::app.datagrid.mass-ops.delete-success', ['resource' => 'Attribute Family']),
                ]);
            }

            return response([
                'message' => __('admin::app.datagrid.mass-ops.partial-action', ['resource' => 'Attribute Family']),
            ]);
        } else {
            return response([
                'message' => __('admin::app.datagrid.mass-ops.method-error'),
            ]);
        }
    }
}