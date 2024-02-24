<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class ExceptionSubscriber implements EventSubscriberInterface
{
    /**
     * Handles kernel exceptions and sets an appropriate response.
     *
     * @param ExceptionEvent $event The exception event.
     * @return void
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof HttpException) {
            $data = [
                'status' => $exception->getStatusCode(),
                'message' => $exception->getMessage()
            ];

            $event->setResponse(new JsonResponse($data));
        } else {
            $data = [
                'status' => 500, // The status does not exist because it is not an HTTP exception, so we set 500 by default.
                'message' => $exception->getMessage()
            ];

            $event->setResponse(new JsonResponse($data));
        }
    }

    /**
     * Returns an array of subscribed events.
     *
     * This method is used to define the events to which the implementing class should listen and
     * handle. It should return an array where the keys are event names and the values are the
     * corresponding event handler methods.
     *
     * @return array An array of subscribed events where the keys represent the event names and the
     *               values represent the event handler methods.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }
}
