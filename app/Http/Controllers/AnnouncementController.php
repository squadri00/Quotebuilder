<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        $business = Auth::user()->business;

        return view('announcements.index', [
            'announcements' => Announcement::allForBusiness($business, Auth::id()),
        ]);
    }

    public function dismiss(Announcement $announcement): RedirectResponse
    {
        $business = Auth::user()->business;

        abort_unless(
            Announcement::query()->whereKey($announcement->id)->matchingBusiness($business)->exists(),
            403
        );

        $announcement->dismiss(Auth::id());

        return back();
    }
}
