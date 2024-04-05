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

        if ($exception instanceof NotFoundHttpException) {
            if (in_array($route, ['detailBuyer', 'detailProduct', 'updateBuyer', 'deleteBuyer'])) {
                $event->setResponse(
                    $this->createErrorResponse(404, 'The resource you are requesting does not exist' )
                );
            } else {
                $event->setResponse(new RedirectResponse('/api/doc'));
            }
        } else {
            $event->setResponse($this->createErrorResponse(500, 'A server problem has occurred'));
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

    /**
     * Creates a JSON response with an error message.
     *
     * This method takes an integer status code and a string message, and creates a JSON response
     * containing these values. The status code represents the HTTP status of the response, while the
     * message provides a human-readable error message.
     *
     * @param int $status The HTTP status code for the response.
     * @param string $message The error message to be included in the response.
     *
     * @return JsonResponse A JSON response containing the status code and error message.
     */
    private function createErrorResponse(int $status, string $message): JsonResponse
    {
        $data = [
            'status' => $status,
            'message' => $message
        ];
        return new JsonResponse($data);
    }
}
