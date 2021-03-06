@extends('layouts.app')

@section('content')


<div class="container" id="members">

	@can('edit-member', $member)
		<edit-member-affiliates member-id="{{ $member_id }}"></edit-member>
	@else
		<p class="error">Sorry, you must be a validated GNZ member to see the member details.</p>

		<p>If you are a member of GNZ, use your <a href="/user/account">user account</a> page to validate your GNZ membership.
	@endcan
</div>

@endsection