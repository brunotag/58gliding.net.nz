<h2>The member {{$member->first_name}} {{$member->last_name}} has changed membership type</h2>

<p>
	From: @isset($from_type->name) {{ $from_type->name}} @endisset <br>
	To:  @isset($to_type->name) {{ $to_type->name}} @endisset 
</p>

<p>
	Edit the user:<br>
	<a href="{{env('APP_URL')}}/members/{{$member->id}}/edit">{{env('APP_URL')}}//members/{{$member->id}}/edit</a>
</p>