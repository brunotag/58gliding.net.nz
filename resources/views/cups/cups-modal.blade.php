{!! Modal::start($modal) !!}
<div class="form-group row">
    <label for="input_name" class="col-sm-2 col-form-label">Name:</label>
    <div class="col-sm-10">
        <input type="text" class="form-control" id="input_name" name="name"
            placeholder="Enter name" value="{{ isset($cup) ? $cup->name : old('name')}}">
    </div>
</div>
<div class="form-group row">
    <label for="input_description" class="col-sm-2 col-form-label">Description:</label>
    <div class="col-sm-10">
        <input type="text" class="form-control" id="input_description" name="description"
            placeholder="Enter Description" value="{{ isset($cup) ? $cup->description : old('description')}}">
    </div>
</div>
{!! Modal::end() !!}