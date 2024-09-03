<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use App\Repositories\BlockUserRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class BuilderController extends Controller
{
    /**Index */
    public function index(Request $request)
    {
        $conversationId = $request->get('conversationId');
        $data['conversationId'] = !empty($conversationId) ? $conversationId : 0;

        /** @var UserRepository $userRepository */
        $userRepository = app(UserRepository::class);
        /** @var BlockUserRepository $blockUserRepository */
        $myContactIds = $userRepository->myContactIds();

        /** @var BlockUserRepository $blockUserRepository */
        $blockUserRepository = app(BlockUserRepository::class);
        list($blockUserIds, $blockedByMeUserIds) = $blockUserRepository->blockedUserIds();

        $data['users'] = User::toBase()
            ->limit(50)
            ->orderBy('name')
            ->select(['name', 'id'])
            ->pluck('name', 'id')
            ->except(getLoggedInUserId());
        $data['enableGroupSetting'] = isGroupChatEnabled();
        $data['membersCanAddGroup'] = canMemberAddGroup();
        $data['myContactIds'] = $myContactIds;
        $data['blockUserIds'] = $blockUserIds;
        $data['blockedByMeUserIds'] = $blockedByMeUserIds;

        /** @var Setting $setting */
        $setting = Setting::where('key', 'notification_sound')->pluck('value', 'key')->toArray();
        if (isset($setting['notification_sound'])) {
            $data['notification_sound'] = app(Setting::class)->getNotificationSound($setting['notification_sound']);
        }
        return view('builder.index', compact('data', 'data'));
    }
}