<template>
<div>

	<h1><a href="/events">Events</a> » <a :href="'/events/' + event.slug">{{event.name}}</a> » Edit</h1>

	<form @submit="save">
		<div class="row">

			<div class="form-group col-md-6">
				<label for="featured" class="float-right">
					<input id="featured" type="checkbox" v-model="event.featured" :value="true">
					Share to all other clubs
				</label>
				<label for="entryform_open" class="float-right mr-4">
					<input class="" id="entryform_open" type="checkbox" v-model="event.entries_active" :value="true">
					Entry Form Open
				</label>

				
				<label for="name" class="col-xs-6 col-form-label">Name</label>
				<div class="col-xs-6">
					<input type="text" class="form-control" id="name" v-model.lazy="event.name">
				</div>
			</div>

			<div class="col-md-6">
				<div class="row">

					<div class="form-group col-sm-6">
						<label for="slug" class="col-form-label">Slug</label>
						<input type="text" class="form-control" id="slug" v-on:change="event.slug = slug(event.slug)" v-model.lazy="event.slug">

					</div>

					<div class="form-group col-sm-6">
						<label for="event_type" class="col-xs-6 col-form-label">Event Type</label>
						<div class="col-xs-6">
							<div class="form-inline">
								<select v-model="event.type" id="event_type" class="form-control">
									<option :value="null">Select a type of event...</option>
									<option :value="eventType.code" v-for="eventType in eventTypes()">{{eventType.name}}</option>
								</select>
							</div>
						</div>
					</div>

				</div>
			</div>

		</div>
		<div class="row">

			<div class="col-md-6">
				<div class="row">
					<div class="form-group col-6">
						<label for="start_date" class="col-form-label">Start Date<span v-show="event.type=='competition'"> (Inc. Practice Days)</span></label>
						<v-date-picker id="start_date" v-model="event.start_date" :locale="{ id: 'start_date', firstDayOfWeek: 2, masks: { weekdays: 'WW', L: 'DD/MM/YYYY' } }" :popover="{ visibility: 'click' }"></v-date-picker>
					</div>

					<div class="form-group col-6">
						<label for="end_date_checkbox" class="col-form-label">
							<input type="checkbox" id="end_date_checkbox" v-model="hasEndDate" v-on:click="event.end_date==null ? event.end_date=event.start_date : 0">
							End Date <span v-show="hasEndDate">({{dateDiffDays(event.start_date, event.end_date)}})</span>
						</label>
						<div v-show="hasEndDate">
							<v-date-picker v-model="event.end_date" :locale="{ id: 'end_date', firstDayOfWeek: 2, masks: { weekdays: 'WW', L: 'DD/MM/YYYY' } }" :popover="{ visibility: 'click' }"></v-date-picker>
						</div>
					</div>
				</div>
			</div>

			<div class="col-md-6">
				<div class="row">

					<div class="col-md-3 col-6">
						<label for="location" class="col-form-label">Start Time</label>
						<div class="">
							<input placeholder="4:30pm or 16:00" class="form-control" id="location"  v-model="event.start_time">
						</div>
					</div>
					<div class="col-md-3 col-6">
						
						<label for="location" class="col-form-label">End Time</label>
						<div class="">
							<input placeholder="4:30pm or 16:00" class="form-control" id="location" v-model="event.end_time">
						</div>
					</div>

					<div class="col-md-6">
						<label for="location" class="col-form-label">Location e.g. Matamata</label>
						<div class="">
							<input type="text" class="form-control" id="location" v-model="event.location">
						</div>
					</div>

				</div>
			</div>
		</div>

		<div class="row">
			<div class="form-group col-md-3">
				<input type="submit" value="Save Changes" class="btn btn-outline-dark mt-2">
				<input type="submit" v-on:click="returnOnSave=true" value="Save &amp; Return" class="btn btn-primary mt-2">
			</div>
		</div>

		<div class="row">

			<div class="form-group col-md-6">
				<label for="website" class="col-xs-6 col-form-label">Website e.g. http://gliding.co.nz/</label>
				<div class="col-xs-6 flex">
					<input class="form-control" id="website" type="text" v-model="event.website">
				</div>
			</div>
			<div class="form-group col-md-3">
				<label for="facebook" class="col-xs-3 col-form-label">Facebook e.g glidingnewzealand</label>
				<div class="col-xs-6">
					<input class="form-control" id="facebook" type="text" v-model="event.facebook">
				</div>
			</div>
			<div class="form-group col-md-3">
				<label for="instagram" class="col-xs-3 col-form-label">Instagram e.g. glidingnz</label>
				<div class="col-xs-6">
					<input class="form-control" id="instagram" type="text" v-model="event.instagram">
				</div>
			</div>

		</div>

		<div class="row">

			<div class="form-group col-md-4">
				<label for="website" class="col-xs-6 col-form-label">Contact Name (Warning: Public)</label>
				<div class="col-xs-6 flex">
					<input class="form-control" id="contact-name" type="text" v-model="event.organiser_name">
				</div>
			</div>
			<div class="form-group col-md-4">
				<label for="website" class="col-xs-6 col-form-label">Phone (Warning: Public)</label>
				<div class="col-xs-6 flex">
					<input class="form-control" id="contact-phone" type="text" v-model="event.organiser_phone">
				</div>
			</div>
			<div class="form-group col-md-4">
				<label for="website" class="col-xs-6 col-form-label">Contact Email (Warning: Public)</label>
				<div class="col-xs-6 flex">
					<input class="form-control" id="website" type="text" v-model="event.email">
				</div>
			</div>

		</div>


		<div class="row">

			<div class="col-md-6">
				<div class="row">
					
					<div class="col-6">
						<label for="cost" class="col-form-label">Cost</label>
							
						<div class="form-inline">
							$ <input id="cost" type="text" class="form-control ml-2 col-4" v-model="event.cost" size="4">
						</div>
					</div>


					<div class="col-6"  v-show="flyingEvent">
						<label for="practice_days" class="col-form-label">Practice Days</label>
							
						<div class="form-inline">
							<input id="practice_days" type="text" class="form-control mr-2" v-model="event.practice_days" size="4"> days
						</div>
					</div>

				</div>
			</div>

			<div class="form-group col-md-6" v-show="flyingEvent">
				<div class="row">
					
					
					<div class="col-6">
						<label for="earlybird" class="col-form-label">Earlybird End Date (inclusive)</label>
							
						<div class="form-inline">
							<v-date-picker id="earlybird" v-model="event.earlybird" :locale="{ id: 'earlybird', firstDayOfWeek: 2, masks: { weekdays: 'WW', L: 'DD/MM/YYYY' } }" :popover="{ visibility: 'click' }"></v-date-picker>
						</div>
					</div>

					<div class="col-6">
						<label for="cost_earlybird" class="col-form-label">Earlybird Cost</label>
							
						<div class="form-inline">
							$ <input id="cost_earlybird" type="text" class="form-control ml-2 col-4" v-model="event.cost_earlybird" size="4">
						</div>
					</div>
				
				</div>
			</div>
		</div>




		<div class="row">

			<div class="form-group col-md-6">
				<label for="details" class="col-xs-6 col-form-label">Page Details (Markdown available)</label>
				<div class="col-xs-6">
					<autosize-textarea>
						<textarea type="text" class="form-control" id="details" v-model="event.details" rows="3"></textarea>
					</autosize-textarea>

					<input type="submit" value="Save Changes" class="btn btn-outline-dark mt-2">
					<input type="submit" v-on:click="returnOnSave=true" value="Save &amp; Return" class="btn btn-primary mt-2">
				</div>
			</div>
			<div class="form-group col-md-6">
				<label for="start_date" class="col-xs-6 col-form-label">Page Preview</label>
				<div class="col-xs-6">

					<div class="card">
						<div v-if="event.details" class="card-body" v-html="compiledMarkdown"></div>
						<div v-if="!event.details" class="card-body" >&nbsp;</div>
					</div>

				</div>
			</div>

		</div>


		<div class="row" v-if="flyingEvent">

			<div class="form-group col-md-6">
				<label for="terms" class="col-xs-6 col-form-label">Terms &amp; Conditions for Entry Form (Markdown available)</label>
				<div class="col-xs-6">
					<autosize-textarea>
						<textarea type="text" class="form-control" id="terms" v-model="event.terms" rows="3"></textarea>
					</autosize-textarea>
					<input type="submit" value="Save Changes" class="btn btn-outline-dark mt-2">
					<input type="submit" v-on:click="returnOnSave=true" value="Save &amp; Return" class="btn btn-primary mt-2">
				</div>
			</div>
			<div class="form-group col-md-6">
				<label for="start_date" class="col-xs-6 col-form-label">Terms Preview</label>
				<div class="col-xs-6">

					<div class="card">
						<div v-if="event.terms" class="card-body" v-html="compiledTermsMarkdown"></div>
						<div v-if="!event.terms" class="card-body" >&nbsp;</div>
					</div>

				</div>
			</div>

		</div>


		<div class="row" v-if="flyingEvent">

			<div class="form-group col-md-6">
				<label for="soaringspot_api_secret" class="col-xs-6 col-form-label">SoaringSpot API Secret</label>
				<div class="col-xs-6">
					<input class="form-control" id="soaringspot_api_secret" type="text" v-model="event.soaringspot_api_secret">
				</div>
			</div>
			<div class="form-group col-md-6">
				<label for="soaringspot_api_client_id" class="col-xs-6 col-form-label">SoaringSpot API Client ID</label>
				<div class="col-xs-6">
					<input class="form-control" id="soaringspot_api_client_id" type="text" v-model="event.soaringspot_api_client_id">
				</div>
			</div>

		</div>


		<div class="row">

			<div class="form-group col-md-6">
				<label class="col-xs-6 col-form-label">Catering Available</label><br>
				
				<label for="breakfasts" class="mr-2"><input type="checkbox" id="breakfasts" :value="true" v-model="event.catering_breakfasts"> Breakfasts</label>
				<label for="lunches" class="mr-2"><input type="checkbox" id="lunches" :value="true" v-model="event.catering_lunches"> Lunches</label>
				<label for="dinners" class="mr-2"><input type="checkbox" id="dinners" :value="true" v-model="event.catering_dinners"> Dinners</label>
				<label for="final_dinner" class="mr-2"><input type="checkbox" id="final_dinner" :value="true" v-model="event.catering_final_dinner"> Final Dinner</label>

			</div>


			<div class="form-group col-md-6" v-if="flyingEvent">
				<label class="col-xs-6 col-form-label">Classes for this Event</label><br>

				<span v-for="availableClass in availableClasses" class="mr-4">
					<label :for="'available_class'+availableClass.id">
						<input type="checkbox" :id="'available_class'+availableClass.id" @change="toggleClass(availableClass.id)" :value="true" :checked="selectedClass(availableClass.id)">&nbsp;{{availableClass.name}}
					</label>
				</span>
			</div>


		</div>


	</form>


</div>
</template>

<script>
import common from '../../mixins.js';
var marked = require('marked');
export default {
	mixins: [common],
	data: function() {
		return {
			event: [],
			newDutyName: '',
			hasEndDate: false,
			returnOnSave: false,
			selectedClasses: [],
			availableClasses: [],
		}
	},
	props: ['orgId', 'eventId'],
	created: function() {
		this.loadEvent();
		this.loadAvailableClasses();
		this.loadSelectedClasses();
	},
	computed: {
		compiledMarkdown: function () {
			return marked(this.event.details, { sanitize: true })
		},
		compiledTermsMarkdown: function () {
			return marked(this.event.terms, { sanitize: true })
		},
		flyingEvent: function() {
			switch (this.event.type)
			{
				case 'competition':
				case 'camp': 
				case 'training': 
				case 'course': 
					return true;
					break;
			}
			return false;
		}
	},
	methods: {
		loadEvent: function() {
			var that = this;
			window.axios.get('/api/events/' + this.eventId).then(function (response) {
				that.event = response.data.data;
				if (that.event.start_date!=that.event.end_date && that.event.end_date) {
					that.hasEndDate=true;
				}
				that.event.start_date = that.$moment(that.event.start_date).toDate();
				if (that.event.end_date) that.event.end_date = that.$moment(that.event.end_date).toDate();
				that.event.earlybird = that.$moment(that.event.earlybird).toDate();
				// if (that.event.start_time) that.event.start_time = that.$moment(that.event.start_time, "HH:mm:ss").format("HH:mm");
				// if (that.event.end_time) that.event.end_time = that.$moment(that.event.end_time, "HH:mm:ss").format("HH:mm");
			});
		},
		save: function(e) {
			var that = this;

			// shallow copy the object so we can alter the dates
			let event = Object.assign({}, this.event);
			event.start_date = this.apiDateFormat(event.start_date);
			if (that.hasEndDate) {
				event.end_date = this.apiDateFormat(event.end_date);
			} else {
				event.end_date = event.start_date;
			}
			
			event.earlybird = this.apiDateFormat(event.earlybird);

			window.axios.put('/api/events/' + this.eventId, event).then(function (response) {
				messages.$emit('success', 'Event "' + that.event.name + '" Updated');
				if (that.returnOnSave) {
					window.location.href = "/events/" + event.slug;
				}
			}).catch(
				function (error) {
					var errors = Object.entries(error.response.data.errors);
					for (const [name, error] of errors) {
						messages.$emit('error', `${error}`);
					}
				}
			);
			e.preventDefault();
		},
		selectedMember: function(member)
		{
			Vue.set(this.event, 'organiser_member_id', member.id);
			//this.event.organiser_member_id = member.id;
		},
		loadSelectedClasses: function() {
			var that = this;
			that.selectedClasses = [];
			window.axios.get('/api/v1/events/' + this.eventId + '/classes').then(function (response) {
				var selectedClasses = response.data.data;
				// just get the IDs into the array
				for (var i=0; i<selectedClasses.length; i++) {
					that.selectedClasses.push(selectedClasses[i].class_id);
				}
			});
		},
		loadAvailableClasses: function() {
			var that = this;
			window.axios.get('/api/v1/classes').then(function (response) {
				that.availableClasses = response.data.data;
			});
		},
		selectedClass: function(class_id)
		{
			return this.selectedClasses.includes(class_id);
		},
		toggleClass: function(class_id)
		{
			if (this.selectedClass(class_id)) {
				// remove it
				this.unlinkClass(class_id);
				const index = this.selectedClasses.indexOf(class_id);
				if (index > -1) {
					this.selectedClasses.splice(index, 1);
				}
			} else {
				// add it
				this.linkClass(class_id);
				this.selectedClasses.push(class_id);
			}
		},
		linkClass: function(class_id)
		{
			window.axios.post('/api/v1/classes/' + class_id + '/link', this.event).then(function (response) {
				
			});
		},
		unlinkClass: function(class_id)
		{
			window.axios.post('/api/v1/classes/' + class_id + '/unlink', this.event).then(function (response) {
				
			});
		}
	}
}
</script>
