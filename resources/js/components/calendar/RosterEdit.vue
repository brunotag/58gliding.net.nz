<style>
	.edit-roster-table .no-wrap {
		white-space: nowrap !important;
	}

	.compact-btn {
		padding: 0;
	}
</style>

<template>
	<div>

		<calendar-nav active="edit-roster" title="Edit Roster"></calendar-nav>
		
		<div class="form-inline form-group">
			<label for="searchAllClubs">
				<input id="searchAllClubs" class="form-control mr-1" type="checkbox" v-model="searchAllClubs" v-if="Laravel.clubAdmin==true"  :value="true">
				Search Members of All Clubs (Useful for multi-club tow pilots)
			</label>
		</div>


		<table class="edit-roster-table table table-striped table-sm collapsable">
			<tr class="d-none d-md-table-row">
				<th>Date</th>
				<th>Available</th>
				<template v-for="duty in defaultDuties">
					<th>{{duty.name}}</th>
				</template>
			</tr>
			<template v-for="(day, dayIndex) in results">
				<tr >
					<td>
						<b>{{renderDate(day.day_date)}}</b>
						<p v-if="day.description" v-html="renderDescription(day.description)"></p>
					</td>
					<td>
						<span v-if="day.winching"><span class="fa fa-check"></span> Winching</span>
						<span v-if="day.towing"><span class="fa fa-check"></span> Towing</span>
						<span v-if="day.training"><span class="fa fa-check"></span> Training</span>
						<span v-if="day.trialflights"><span class="fa fa-check"></span> Trial Flights</span>
					</td>
					<template v-for="(duty, dutyIndex) in defaultDuties">
						<td class="no-wrap">

							<b class="d-md-none">{{duty.name}}</b>

							<!-- list existing roster items -->
							<roster-edit-item v-for="(rosterItem, rosterIndex) in getDaysRosters(day.id, duty.id)" v-bind:key="rosterItem.id" :roster="rosterItem" :member="rosterItem.member" v-on:delete="deleteEvent(rosterItem)" :tabindex="100 * (dayIndex + (results.length * dutyIndex) + 100)"></roster-edit-item>

							<button v-show="getDaysRosters(day.id, duty.id).length>0 && (!getShowAdd(duty, day))" class="btn badge badge-dark" v-on:click="showAdd(duty, day)" :tabindex="100 * (dayIndex + (results.length * dutyIndex) + 100) + 5"><span class="fa fa-plus"></span> Add Another</button>

							<!-- allow adding a new one -->
							<roster-add-item  v-show="(getDaysRosters(day.id, duty.id).length==0) || (getShowAdd(duty, day))" v-on:add="addEvent" :orgId="orgId" :day="day" :duty="duty" :tabindex="100 * (dayIndex + (results.length * dutyIndex) + 100)" v-bind:search-all-clubs="searchAllClubs"></roster-add-item>

						</td>
					</template>
				</tr>
			</template>
		</table>

	</div>
</template>


<script>
	import common from '../../mixins.js';
	//import VCalendar from 'v-calendar';
	import moment from 'moment';
	Vue.prototype.$moment = moment;

	export default {
		mixins: [common],
		props: ['orgId'],
		data() {
			return {
				duties: [],
				results: [],
				roster: [],
				showCustomModal: false,
				showAddPanels: {},
				customAddDay: null,
				customAddDuty: null,
				searchAllClubs: false
			}
		},
		mounted() {
			this.loadDays();
			this.loadDuties();
			this.loadRoster();
		},
		computed: {
			defaultDuties: function() {
				return this.duties.filter(function(duty) {
					return !duty.custom;
				});
			},
			customDuties: function() {
				return this.duties.filter(function(duty) {
					return duty.custom;
				});
			}
		},
		methods: {
			loadDays: function() {
				var that = this;
				// select all days from today onwards
				window.axios.get('/api/days?org_id=' + this.orgId + '&start_date=' + this.$moment().format('YYYY-MM-DD')).then(function (response) {
					that.results=[];
					that.results = response.data.data;
				});
			},
			openCustomModal: function(day) {
				this.customAddDay = day;
				this.showCustomModal = true;
			},
			closeCustomModal: function() {
				this.showCustomModal = false;
			},
			loadDuties: function() {
				var that = this;
				window.axios.get('/api/duties/?org_id=' + this.orgId).then(function (response) {
					that.duties = response.data.data;
				});
			},
			loadRoster: function() {
				var that = this;
				window.axios.get('/api/roster/?org_id=' + this.orgId + '&start_date=' + this.$moment().format('YYYY-MM-DD')).then(function (response) {
					that.roster = response.data.data;
				});
			},
			renderDate: function(date) {
				return this.$moment(date).format('ddd, MMM Do YY');
			},
			renderDescription: function(description) {
				if (description==null) return null;
				return description.replace(/(?:\r\n|\r|\n)/g, '<br>');
			},
			getDaysRosters: function(day_id, duty_id) {
				var result = this.roster.filter(function(roster) {
					if (roster.day_id==day_id && roster.duty_id==duty_id) return true;
				});
				return result;
			},
			deleteEvent: function(rosterItem) {
				this.roster.splice(this.roster.indexOf(rosterItem), 1);
			},
			addEvent: function(newRoster) {
				this.roster.push(newRoster);
				this.hideAdd(newRoster.duty_id, newRoster.day_id);
				this.showCustomModal = false;
			},
			showAdd: function(duty, day) {
				var key = 'a' + duty.id + '-' + day.id;
				this.$set(this.showAddPanels, key, true);
			},
			hideAdd: function(duty_id, day_id) {
				var key = 'a' + duty_id + '-' + day_id;
				this.$set(this.showAddPanels, key, false);
			},
			getShowAdd: function(duty, day) {
				var key = 'a' + duty.id + '-' + day.id;
				return this.showAddPanels[key];
			}
		}

	}
</script>