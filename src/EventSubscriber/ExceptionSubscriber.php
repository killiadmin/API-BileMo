<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class ExceptionSubscriber implements EventSubscriberInterface
{
    /**
     * Handles kernel exceptions and sets the appropriate response based on the exception type.
     *
     * @param ExceptionEvent $event The exception event.
     * @return void
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof HttpException && $exception->getStatusCode() === 404) {
            $event->setResponse(new RedirectResponse('/api/doc'));
        } elseif ($exception instanceof HttpException) {
            $data = [
                'status' => $exception->getStatusCode(),
                'message' => $exception->getMessage()
            ];
            $event->setResponse(new JsonResponse($data));

        } else {
            $data = [
                'status' => 500,
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
