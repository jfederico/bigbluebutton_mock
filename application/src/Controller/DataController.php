<?php

namespace App\Controller;

use App\Component\HttpFoundation\ErrorResponse;
use App\Component\HttpFoundation\MessageResponse;
use App\Component\HttpFoundation\MeetingInfoResponse;
use App\Component\HttpFoundation\MeetingSummaryResponse;
use App\Component\HttpFoundation\XmlResponse;
use App\Entity\Attendee;
use App\Entity\Meeting;
use App\Entity\Recording;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use stdClass;

abstract class DataController extends AbstractController
{
    protected function handleRoomNotFound(?string $meetingID): ErrorResponse
    {
        return new ErrorResponse(
            'notFound',
            'We could not find a meeting with that meeting ID',
            'FAILED',
            404
        );
    }

    protected function handleAccessDenied(): ErrorResponse
    {
        return new ErrorResponse(
            'denied',
            'Access denied',
            'FAILED',
            403
        );
    }

    protected function findRoomConfiguration(?string $meetingID): ?Meeting
    {
        $meeting = $this->getDoctrine()
             ->getRepository(Meeting::class)
             ->findOneBy(['meetingID' => $meetingID]);

        return $meeting;
    }

    protected function getNamedMetadataFromRequest(Request $request, array $items, bool $fillDefaults = true): array
    {
        $data = [];
        foreach ($items as $itemName => $default) {
            $paramName = "meta_{$itemName}";
            if ($request->query->has($paramName)) {
                $data[$itemName] = $request->query->get($paramName);
            } else if ($fillDefaults) {
                $data[$itemName] = $default;
            }
        }

        return $data;
    }

    protected function getBaseMetadata(): array
    {
        return [
            'bbb-context' => '12345',
            'bbb-context-id' => '',
            'bbb-context-label' => '',
            'bbb-context-name' => '',
            'bbb-origin' => 'Moodle',
            'bbb-origin-server-common-name' => 'http://example.com/',
            'bbb-origin-server-name' => 'BBB Moodle',
            'bbb-origin-tag' => "moodle-mod_bigbluebuttonbn (PLUGINVERSION)",
            'bbb-origin-version' => "RELEASE",
        ];
    }

    protected function getMetadataFromRequest(Request $request): array
    {
        return $this->getNamedMetadataFromRequest($request, $this->getBaseMetadata());
    }

    protected function getRecordingMetadataFromRequest(Request $request, bool $fillDefaults = true): array
    {
        $items = array_merge($this->getBaseMetadata(), [
            'bbb-recording-description' => '',
            'bbb-recording-name' => '',
            'bbb-recording-tags' => '',
            'bn-recording-ready-url' => 'http://example.com/broker',
            'bn-presenter-name' => 'Kevin Presenter',
            'isBreakout' => false,
        ]);

        $metadata = $this->getNamedMetadataFromRequest($request, $items, $fillDefaults);

        if (empty($metadata['bbb-recording-name']) && $fillDefaults) {
            $metadata['bbb-recording-name'] = $metadata['bbb-context-name'];
        }

        return $metadata;
    }
}
