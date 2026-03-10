<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class BestQflowAvailabilityService
{
    /** @var BestQflowEventRepository */
    protected $eventRepository;

    /** @var BestQflowCartRepository */
    protected $cartRepository;

    /** @var int */
    protected $reservationWindowMinutes;

    public function __construct(
        BestQflowEventRepository $eventRepository,
        BestQflowCartRepository $cartRepository,
        int $reservationWindowMinutes = 30
    ) {
        $this->eventRepository = $eventRepository;
        $this->cartRepository = $cartRepository;
        $this->reservationWindowMinutes = max(1, $reservationWindowMinutes);
    }

    public function getAvailability(int $idEvent, int $excludeCartId = 0): array
    {
        $event = $this->eventRepository->getById($idEvent);

        if (!$event) {
            return [
                'stock_limit' => 0,
                'reserved_qty' => 0,
                'sold_qty' => 0,
                'available_qty' => 0,
            ];
        }

        $stockLimit = (int) $event['stock_limit'];
        $reservedQty = $this->cartRepository->sumReservedQtyByEvent(
            $idEvent,
            $this->reservationWindowMinutes,
            $excludeCartId
        );
        $soldQty = 0;
        $availableQty = max(0, $stockLimit - $reservedQty - $soldQty);

        return [
            'stock_limit' => $stockLimit,
            'reserved_qty' => $reservedQty,
            'sold_qty' => $soldQty,
            'available_qty' => $availableQty,
        ];
    }

    public function canReserve(int $idEvent, int $requestedQty, int $excludeCartId = 0): bool
    {
        if ($requestedQty <= 0) {
            return false;
        }

        $availability = $this->getAvailability($idEvent, $excludeCartId);

        return $availability['available_qty'] >= $requestedQty;
    }
}
