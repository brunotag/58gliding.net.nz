<h2>GNZ Approval Needed For {{$member->first_name}} {{$member->last_name}}</h2>

See all members waiting for approval at:<br>
<a href="{{env('APP_URL')}}/members/?type=pending_approval&ex_members=true">{{env('APP_URL')}}/members/?type=pending_approval&ex_members=true</a><br>

<br>

Edit the user:<br>
<a href="{{env('APP_URL')}}/members/{{$member->id}}/edit">{{env('APP_URL')}}//members/{{$member->id}}/edit</a>