<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Membertype;

class NotifyGnzMemberTypeChange extends Mailable
{
	use Queueable, SerializesModels;

	public $member;
	public $from_type;
	public $to_type;

	/**
	 * Create a new message instance.
	 *
	 * @return void
	 */
	public function __construct($member, $from_membershiptype_id, $to_membershiptype_id)
	{
		$from_type = Membertype::where('id', '=', $from_membershiptype_id)->first();
		$to_type = Membertype::where('id', '=', $to_membershiptype_id)->first();

		$this->member = $member;
		$this->from_type = $from_type;
		$this->to_type = $to_type;
	}

	/**
	 * Build the message.
	 *
	 * @return $this
	 */
	public function build()
	{
		return $this->subject('GNZ Member Change to ')->view('emails.gnzmemberchangetype');
	}
}
