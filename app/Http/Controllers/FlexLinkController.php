<?php

namespace App\Http\Controllers;

use App\Models\Show;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FlexLinkController extends Controller
{
    private const FILE = 'flex-id.json';

    public function getOrCreate(int $showId): JsonResponse
    {
        $show = Show::findOrFail($showId);

        $data = $this->read();

        if (isset($data['show_id']) && $data['show_id'] === $showId) {
            return response()->json(['uid' => $data['uid']]);
        }

        $uid = Str::uuid()->toString();

        Storage::disk('local')->put(self::FILE, json_encode([
            'show_id' => $showId,
            'slug'    => $show->slug,
            'uid'     => $uid,
        ]));

        return response()->json(['uid' => $uid]);
    }

    public function showByUid(string $uid): JsonResponse
    {
        $data = $this->read();

        if (empty($data['uid']) || $data['uid'] !== $uid) {
            return response()->json(['error' => 'Invalid flex link'], 403);
        }

        $show = Show::with('performances')
            ->where('slug', $data['slug'])
            ->firstOrFail();

        return response()->json(['show' => $show]);
    }

    private function read(): array
    {
        if (!Storage::disk('local')->exists(self::FILE)) {
            return [];
        }

        return json_decode(Storage::disk('local')->get(self::FILE), true) ?? [];
    }
}
