<?php


namespace SchemaCrawler\Exceptions;


use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{

    protected $handler;

    protected $dontReport = [
        InvalidSchema::class
    ];

    /**
     * Handler constructor.
     * @param ExceptionHandler $handler
     */
    public function __construct(ExceptionHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * Report or log an exception.
     *
     * @param  \Exception $e
     * @return void
     * @throws Exception
     */
    public function report(Exception $e)
    {
        return $this->handler->report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Exception               $e
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function render($request, Exception $e)
    {
        return $this->handler->render($request, $e);
    }

    /**
     * Render an exception to the console.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface $output
     * @param  \Exception                                        $e
     * @return void
     */
    public function renderForConsole($output, Exception $e)
    {
        return $this->handler->renderForConsole($output, $e);
    }
}