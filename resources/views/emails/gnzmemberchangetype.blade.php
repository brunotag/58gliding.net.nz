<h2>The member {{$member->first_name}} {{$member->last_name}} has changed membership type</h2>

<p>
	From: {{$from_type->name}}<br>
	To:  {{$to_type->name}}
</p>

<p>
	Go direct to edit the user:<br>
	<a href="{{env('APP_URL')}}/members/{{$member->id}}/edit">{{env('APP_URL')}}//members/{{$member->id}}/edit</a>
</p>