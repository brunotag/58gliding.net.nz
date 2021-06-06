<style>
	@media (max-width:768px) {
		.ratings-table td {
			display: block;
		}
		.ratings-table th {
			display: none;
		}
		.name {
			font-weight: bold;
		}
	}
</style>

<template>
	<div class="list-group">
		
		<table class="table table-striped ratings-table">
			<tr>
				<th>Name</th>
				<th class="hidden-xs">GNZ#</th>
				<th>Instructor?</th>
				<th>QGP?</th>
				<th>XCP?</th>
				<th>Passenger Rating?</th>
				<th>BFR</th>
				<th>Medical</th>
				<th>Passengers</th>
				<th class="hidden-xs" v-if="allowsEdit">Edit</th>
			</tr>
			<template v-for="rating in ratings">
				<tr>
					<td style="overflow: auto;">
						<span class="name"><a v-bind:href="'/members/' + rating.id + '/ratings?from=ratings'">{{rating.first_name}} {{rating.last_name}}</a></span>
						<a v-bind:href="'/members/' + rating.id + '/ratings?from=ratings'" class="btn btn-xs btn-primary d-md-none float-right" v-if="allowsEdit">Edit</a>
					</td>
					<td class="hidden-xs">{{rating.nzga_number}}</td>
					<td>
						<span class="d-md-none">Instructor: </span>
							<span class="fa fa-check" v-if="is_instructor(rating)"></span><span class="fa fa-times d-md-none" v-if="!is_instructor(rating)"></span>

					</td>
					<td>
						<span class=" d-md-none">QGP: </span><span class="fa fa-check" v-if="rating.qgp_awarded!=null"></span><span class="fa fa-times d-md-none" v-if="!rating.qgp_awarded"></span>
					</td>
					<td>
						<span class=" d-md-none">XCP: </span><span class="fa fa-check" v-if="rating.xcp_awarded!=null"></span><span class="fa fa-times d-md-none" v-if="!rating.xcp_awarded"></span>
					</td>
					<td>
						<span class=" d-md-none">Passenger Rating: </span><span class="fa fa-check" v-if="rating.passenger_awarded!=null"></span><span class="fa fa-times d-md-none" v-if="!rating.passenger_awarded"></span>
					</td>
					<td v-bind:class="[ bfrGood(rating) ]">
						<span class="fa fa-check" v-if="bfrGood(rating)=='success'"></span>
						<span class="d-md-none">BFR: </span>
						<span class="fa fa-exclamation-triangle" v-if="bfrGood(rating)=='warning'"></span>
						<span class="fa fa-info-circle" v-if="bfrGood(rating)=='info'"></span>
						<span class="fa fa-times error" v-if="bfrGood(rating)=='danger'"></span>
						<span v-if="rating.bfr_expires">
							<span v-if="!ratingExpired(rating.bfr_expires)">Expires</span>
							<span v-if="ratingExpired(rating.bfr_expires)">Expired</span>
							{{rating.bfr_expires_togo}}
						</span> 
						<span v-if="!rating.bfr_expires">No BFR on file</span>
					</td>
					<td v-bind:class="[ medicalGood(rating) ]">
						<span class="fa fa-check" v-if="medicalGood(rating)=='success'"></span>
						<span class="d-md-none">Medical: </span>
						<span class="fa fa-exclamation-triangle" v-if="medicalGood(rating)=='warning'"></span>
						<span class="fa fa-info-circle" v-if="medicalGood(rating)=='info'"></span>
						<span class="fa fa-times error" v-if="medicalGood(rating)=='danger'"></span>
						<span v-if="rating.medical_expires!=null">
							<span v-if="!ratingExpired(rating.medical_expires)">Expires</span>
							<span v-if="ratingExpired(rating.medical_expires)">Expired</span>
							{{rating.medical_expires_togo}}
						</span> 
						<span v-if="rating.medical_awarded && !rating.medical_expires">Never Expires</span>
						<span v-if="!rating.medical_awarded">No medical on file</span>
					</td>
					<td v-bind:class="[ passengersGood(rating) ]">
						<span class="d-md-none">Passengers: </span>
						<span class="fa fa-check" v-if="passengersGood(rating)=='success'"></span>
						<span class="fa fa-exclamation-triangle" v-if="passengersGood(rating)=='warning'"></span>
						<span class="fa fa-times error" v-if="passengersGood(rating)=='danger'"></span>
						<span v-if="!is_qgp_or_xcp(rating)">No QGP, XCP or Passenger Rating.</span>
						<span v-if="!rating.medical_passenger_expires">No medical.</span>
						<span v-if="!rating.bfr_expires || ratingExpired(rating.bfr_expires)"> No BFR.</span>
						<span v-if="ratingNearlyExpired(rating.bfr_expires)"> BFR expires Soon.</span>

						<span v-if="ratingNearlyExpired(rating.medical_passenger_expires)">Medical expires {{rating.medical_passenger_expires_togo}}</span>
						<span v-if="ratingExpired(rating.medical_passenger_expires)">Medical expired {{rating.medical_passenger_expires_togo}}</span>
						<span v-if="passengersGood(rating)=='success'">Medical for passenger expires {{rating.medical_passenger_expires_togo}}</span>
					</td>
					<td class="d-none d-md-table-cell" v-if="allowsEdit">
						<a v-bind:href="'/members/' + rating.id + '/ratings?from=ratings'" class="btn btn-xs btn-primary">Edit</a>
					</td>
				</tr>
				<tr>
					<td colspan="6" class="d-md-none" style="height: 30px;">&nbsp;</td>
				</tr>
			</template>
		</table>

	</div>
</template>

<script>
import common from '../mixins.js';
	import moment from 'moment';
	Vue.prototype.$moment = moment;

export default {
	mixins: [common],
	data: function() {
		return {
			ratings: []
		}
	},
	props: ['org', 'allowsEdit'],
	created: function () {
		this.loadRatings();
	},
	computed: {
	},
	methods: {
		getDomain: function() {
			return window.Laravel.APP_DOMAIN;
		},
		loadRatings: function() {
			var that = this;
			window.axios.get('/api/v1/ratings/report', {params: {org: this.org}}).then(function (response) {
				// success callback
				that.ratings = response.data.data;

				//var timeagoInstance = timeago();
				for (var i=0; i<that.ratings.length; i++) {

					// check if the passenger expire time is more than the medical expire time
					if (that.ratings[i].medical_expires < that.ratings[i].medical_passenger_expires) {
						that.ratings[i].medical_passenger_expires = that.ratings[i].medical_expires;
					}

					// that.ratings[i].bfr_expires_togo = timeagoInstance.format(that.ratings[i].bfr_expires);
					// that.ratings[i].medical_expires_togo = timeagoInstance.format(that.ratings[i].medical_expires);
					// that.ratings[i].medical_passenger_expires_togo = timeagoInstance.format(that.ratings[i].medical_passenger_expires);
					that.ratings[i].bfr_expires_togo = moment(that.ratings[i].bfr_expires).fromNow();
					that.ratings[i].medical_expires_togo = moment(that.ratings[i].medical_expires).fromNow();
					that.ratings[i].medical_passenger_expires_togo = moment(that.ratings[i].medical_passenger_expires).fromNow();

				}
			});
		},
		passengersGood: function(rating) {
			if (!this.is_qgp_or_xcp(rating)) return 'danger';
			if (this.ratingExpired(rating.bfr_expires)) return 'danger';
			if (!rating.bfr_expires) return 'danger';
			if (this.ratingExpired(rating.medical_passenger_expires)) return 'danger';
			if (!rating.medical_passenger_expires) return 'danger';
			if (this.ratingNearlyExpired(rating.medical_passenger_expires)) return 'warning';
			if (this.ratingNearlyExpired(rating.bfr_expires)) return 'warning';
			return 'success';
		},
		bfrGood: function(rating) {
			if (this.ratingExpired(rating.bfr_expires)) return 'danger';
			if (this.ratingNearlyExpired(rating.bfr_expires)) return 'warning';
			if (!rating.bfr_expires) return 'info';
			return 'success';
		},
		medicalGood: function(rating) {
			if (this.ratingExpired(rating.medical_expires)) return 'danger';
			if (this.ratingNearlyExpired(rating.medical_expires)) return 'warning';
			if (!rating.medical_awarded) return 'info';
			return 'success';
		},
		is_qgp_or_xcp: function(rating) {
			return rating.qgp_awarded || rating.xcp_awarded || rating.passenger_awarded;
		},
		is_instructor: function(rating) {
			return rating.instructor || rating.insta_awarded || rating.instb_awarded || rating.instc_awarded || rating.instd_awarded;
		},
	}
}
</script>

<style>
</style>