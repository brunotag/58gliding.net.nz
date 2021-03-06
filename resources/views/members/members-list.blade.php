@extends('layouts.app')

@section('content')


<div class="container-fluid" id="members">

	@can('gnz-member')
		<members></members>
	@else
		<p class="error">Sorry, you must be a validated GNZ member to see the membership list.</p>

		<p>If you are a member of GNZ, use your <a href="/user/account">user account</a> page to validate your GNZ membership.
	@endcan
</div>

@endsection