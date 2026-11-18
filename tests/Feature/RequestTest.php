<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use JeffersonGoncalves\QuebecRequests\Enums\RequestStatus;
use JeffersonGoncalves\QuebecRequests\Enums\RequestType;
use JeffersonGoncalves\QuebecRequests\Events\PrivacyRequestClosed;
use JeffersonGoncalves\QuebecRequests\Events\PrivacyRequestOpened;
use JeffersonGoncalves\QuebecRequests\Events\PrivacyRequestOverdue;
use JeffersonGoncalves\QuebecRequests\Facades\QuebecRequests;
use JeffersonGoncalves\QuebecRequests\Tests\Fixtures\Order;
use JeffersonGoncalves\QuebecRequests\Tests\Fixtures\User;

beforeEach(function () {
    $this->user = User::create(['name' => 'Mary Smith', 'email' => 'maria@example.com', 'tax_id' => '123']);
    Order::create(['user_id' => $this->user->id, 'shipping_address' => 'Main St 1', 'total' => 100]);
    Order::create(['user_id' => $this->user->id, 'shipping_address' => 'Main St 2', 'total' => 200]);

    $other = User::create(['name' => 'John', 'email' => 'joao@example.com']);
    Order::create(['user_id' => $other->id, 'shipping_address' => 'Main St 3', 'total' => 300]);
});

it('opens a request with the legal deadline', function () {
    Event::fake([PrivacyRequestOpened::class]);
    $this->freezeSecond(); // datetime columns drop microseconds

    $request = QuebecRequests::open(RequestType::Access, requester: ['email' => 'maria@example.com']);

    expect($request->status)->toBe(RequestStatus::Pending)
        ->and($request->due_at->equalTo(now()->addDays(30)))->toBeTrue()
        ->and($request->subject)->toBeNull();

    Event::assertDispatched(PrivacyRequestOpened::class);
});

it('exports only the subject data, keyed by table', function () {
    expect(QuebecRequests::export($this->user))->toBe([
        'users' => [['name' => 'Mary Smith', 'email' => 'maria@example.com', 'tax_id' => '123']],
        'orders' => [['shipping_address' => 'Main St 1'], ['shipping_address' => 'Main St 2']],
    ]);
});

it('refuses to fulfil an unverified request', function () {
    QuebecRequests::fulfil(QuebecRequests::open(RequestType::Access, $this->user));
})->throws(LogicException::class, 'Verify');

it('fulfils access with a JSON export on the private disk', function () {
    Storage::fake('local');
    Event::fake([PrivacyRequestClosed::class]);

    $request = QuebecRequests::open(RequestType::Portability);
    QuebecRequests::verify($request, $this->user);
    QuebecRequests::fulfil($request, 'Sent by email');

    $request->refresh();

    expect($request->status)->toBe(RequestStatus::Completed)
        ->and($request->response)->toBe('Sent by email')
        ->and($request->export_path)->toBe("quebec/requests/request-{$request->id}.json")
        ->and(json_decode(Storage::disk('local')->get($request->export_path), true)['orders'])->toHaveCount(2);

    Event::assertDispatched(PrivacyRequestClosed::class);
});

it('fulfils deletion by anonymizing every record of the subject only', function () {
    $request = QuebecRequests::verify(QuebecRequests::open(RequestType::Deletion, $this->user));
    QuebecRequests::fulfil($request);

    $user = $this->user->fresh();

    expect($user->name)->toBe('M*********')
        ->and($user->email)->not->toBe('maria@example.com')
        ->and($user->tax_id)->toBe('123') // Keep: legal retention
        ->and(Order::where('user_id', $user->id)->pluck('shipping_address')->all())->toBe([null, null])
        ->and(Order::whereNotNull('shipping_address')->pluck('shipping_address')->all())->toBe(['Main St 3']);
});

it('leaves manual types to complete()', function () {
    QuebecRequests::fulfil(QuebecRequests::verify(QuebecRequests::open(RequestType::Deindexation, $this->user)));
})->throws(LogicException::class, 'by hand');

it('rejects with a reason and refuses to reopen', function () {
    $request = QuebecRequests::reject(QuebecRequests::open(RequestType::Deletion, $this->user), 'Legal retention');

    expect($request->status)->toBe(RequestStatus::Rejected);

    QuebecRequests::fulfil(QuebecRequests::verify($request));
})->throws(LogicException::class, 'closed');

it('dispatches an event per overdue request', function () {
    Event::fake([PrivacyRequestOverdue::class]);
    QuebecRequests::open(RequestType::Access, $this->user);
    QuebecRequests::complete(QuebecRequests::open(RequestType::Access, $this->user));

    $this->travel(32)->days();
    Artisan::call('quebec:requests-overdue');

    Event::assertDispatchedTimes(PrivacyRequestOverdue::class, 1);
});

it('has no extension unless one is configured', function () {
    $request = QuebecRequests::open(RequestType::Access, $this->user);

    expect(fn () => QuebecRequests::extend($request, 'Busy'))->toThrow(LogicException::class, '30 days');

    config()->set('quebec-requests.extension', '10 days');
    $this->freezeSecond();
    $due = $request->due_at->copy();
    QuebecRequests::extend($request, 'Thousands of records');

    expect($request->fresh()->due_at->equalTo($due->addDays(10)))->toBeTrue()
        ->and(fn () => QuebecRequests::extend($request, 'again'))->toThrow(LogicException::class, 'already extended');
});
