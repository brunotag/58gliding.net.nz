<h2>{{$member->first_name}} {{$member->last_name}} Resigned from GNZ</h2>

GNZ Member Resigned:<br>
<a href="{{env('APP_URL')}}/members/{{$member->id}}">{{env('APP_URL')}}//members/{{$member->id}}</a>