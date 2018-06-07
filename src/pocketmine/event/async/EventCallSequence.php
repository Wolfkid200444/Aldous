<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace pocketmine\event\async;

use pocketmine\event\Event;
use pocketmine\event\EventPriority;
use pocketmine\event\HandlerList;
use pocketmine\event\Listener;
use pocketmine\plugin\EventExecutor;
use pocketmine\plugin\MethodEventExecutor;
use pocketmine\plugin\RegisteredListener;

class EventCallSequence{
	private const STATE_PAUSED = 1;
	private const STATE_CONTINUED = 2;

	/** @var Event */
	private $event;
	/** @var HandlerList */
	private $handlerList;
	/** @var float[][] */
	private $priorityCollectors;
	/** @var \SplObjectStorage|RegisteredListener[]|Listener[]|MethodEventExecutor[] */
	private $listenerMap;
	private $onComplete;
	/** @var \Generator */
	private $generator;

	/** @var array */
	private $listenerStates = [];
	/** @var int */
	private $currentPriority;
	/** @var float|null if paused, value is the timeout timestamp; else, or if continued, value is null */
	private $currentListenerPaused = null;

	public function __construct(Event $event, HandlerList $handlerList, callable $onComplete){
		$this->event = $event;
		$event->setCallSequence($this);
		$this->handlerList = $handlerList;
		$this->priorityCollectors = array_fill_keys(EventPriority::ALL, []);
		$this->listenerMap = new \SplObjectStorage();
		$this->onComplete = $onComplete;

		$this->generator = $this->generate();
	}

	private function generate() : \Generator{
		foreach(EventPriority::ALL as $priority){
			$this->currentPriority = $priority;

			foreach($this->handlerList->getListenersByPriority($priority) as $listener){
				$this->startListener($listener);
				$listener->callEvent($this->event);
				while($this->currentListenerPaused !== null){
					if($this->currentListenerPaused < microtime(true)){
						// TODO add warning message
						$this->currentListenerPaused = null;
						break;
					}
					yield;
				}
			}

			while(!empty($this->priorityCollectors[$priority])){
				foreach($this->priorityCollectors[$priority] as $hash => $timeout){
					if($this->listenerStates[$hash] === self::STATE_CONTINUED){
						unset($this->priorityCollectors[$priority][$hash]);
					}
					if($timeout < microtime(true)){
						// TODO add warning message
						unset($this->priorityCollectors[$priority][$hash]);
					}
				}
				if(empty($this->priorityCollectors[$priority])){
					break;
				}
				yield;
			}
		}

		($this->onComplete)($this->event);
	}

	/**
	 * @return \Generator
	 */
	public function getGenerator() : \Generator{
		return $this->generator;
	}

	private function startListener(RegisteredListener $listener) : void{
		$this->listenerMap[$listener] = $listener;
		$this->listenerMap[$listener->getExecutor()] = $listener;

		$eventListener = $listener->getListener();
		if($this->listenerMap->contains($eventListener)){
			$this->listenerMap[$eventListener] = null; // same listener, different priorities, let's get a different error message
		}else{
			$this->listenerMap[$eventListener] = $listener;
		}
	}

	public function getEvent() : Event{
		return $this->event;
	}

	/**
	 * @param RegisteredListener|Listener|EventExecutor $listener
	 * @param int|null                                  $priority
	 */
	public function pause(object $listener, ?int $priority = null) : void{
		if($priority > $this->currentPriority){
			throw new \InvalidArgumentException("Priority $priority execution has already been completed");
		}

		if(!($listener instanceof RegisteredListener || $listener instanceof Listener || $listener instanceof EventExecutor)){
			throw new \InvalidArgumentException('$listener should be instance of RegisteredListener, Listener or EventExecutor, got ' . get_class($listener));
		}

		if(!$this->listenerMap->contains($listener)){
			throw new \InvalidArgumentException('$listener is not a handler of this event or has not started listening yet');
		}

		if($this->listenerMap[$listener] === null){
			throw new \InvalidArgumentException("Cannot pause by Listener because this Listener has been registered for the event at multiple priorities"); // TODO fix this
		}

		$hash = spl_object_hash($this->listenerMap[$listener]);
		if(isset($this->listenerStates[$hash])){
			throw new \InvalidStateException('Cannot pause the same event twice for the same handler');
		}

		$this->listenerStates[$hash] = self::STATE_PAUSED;

		if($priority === null){
			assert($this->currentListenerPaused === null, "currentListenerPaused should have been checked in singlePauseChecker");
			$this->currentListenerPaused = microtime(true);
		}else{
			if($priority > $this->currentPriority){
				throw new \InvalidStateException("Cannot pause until earlier priority (" . EventPriority::toString($priority) . " is run before " . EventPriority::toString($this->currentPriority) . ")");
			}
			$this->priorityCollectors[$priority][$hash] = microtime(true);
		}
	}

	public function continue(object $listener) : void{
		if(!$this->listenerMap->contains($listener)){
			throw new \InvalidArgumentException('$listener did not pause an event');
		}

		$hash = spl_object_hash($listener);
		assert(isset($this->listenerStates[$hash]));
		if($this->listenerStates[$hash] === self::STATE_CONTINUED){
			throw new \InvalidArgumentException("Event was already continued");
		}

		$this->listenerStates[$hash] = self::STATE_CONTINUED;
	}

	public function tick() : bool{
		if(!$this->generator->valid()){
			return false;
		}

		$this->generator->next();
		return $this->generator->valid();
	}
}
