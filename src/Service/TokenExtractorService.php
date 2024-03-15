<?php

namespace App\Service;

use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Symfony\Component\HttpFoundation\Request;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;

class TokenExtractorService
{
    private JWTEncoderInterface $jwtEncoder;

    public function __construct(JWTEncoderInterface $jwtEncoder)
    {
        $this->jwtEncoder = $jwtEncoder;
    }

    /**
     * Extracts the token from the Authentication header in the given Request object.
     *
     * @param Request $request The request object containing the Authentication header.
     *
     * @return string|null The extracted token, or null if the token is not found.
     */
    public function extractToken(Request $request): ?string
    {
        // Extracting the token from Authentication header
        return explode(' ', $request->headers->get('Authorization'))[1] ?? null;
    }

    /**
     * Decodes the given token.
     *
     * @param string|null $token The token to decode.
     *
     * @return array|null The decoded token as an associative array or null if the token is empty.
     * @throws JWTDecodeFailureException
     */
    public function decodeToken(?string $token): ?array
    {
        // Decoding the token
        $decodedToken = null;

        if ($token) {
            $decodedToken = $this->jwtEncoder->decode($token);
        }

        return $decodedToken;
    }
}
