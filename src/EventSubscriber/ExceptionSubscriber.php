<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class ExceptionSubscriber implements EventSubscriberInterface
{
    /**
     * Handles the kernel exception event.
     *
     * This method is triggered when an exception is thrown during the handling of a request.
     * It checks the route of the request and the type of the exception, and then sets the appropriate
     * response for each case.
     *
     * @param ExceptionEvent $event The exception event object.
     * @return void
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        if (in_array($route, ['detailBuyer', 'detailProduct', 'updateBuyer', 'deleteBuyer'])) {
            if ($exception instanceof NotFoundHttpException) {
                $data = [
                    'status' => $exception->getStatusCode(),
                    'message' => $exception->getMessage()
                ];
                $event->setResponse(new JsonResponse($data));
            }
        } elseif ($exception instanceof NotFoundHttpException) {
            $event->setResponse(new RedirectResponse('/api/doc'));
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
