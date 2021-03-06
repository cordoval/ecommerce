<?php

/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\Component\Form\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Sonata\Component\Basket\BasketInterface;

class BasketResizeFormListener implements EventSubscriberInterface
{
    /**
     * @var FormFactoryInterface
     */
    private $factory;

    /**
     * @var BasketInterface
     */
    private $basket;

    /**
     * @var array
     */
    private $removed = array();

    public function __construct(FormFactoryInterface $factory, BasketInterface $basket)
    {
        $this->factory  = $factory;
        $this->basket   = $basket;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA    => 'preSetData',
            FormEvents::PRE_BIND        => 'preBind',
        );
    }

    /**
     * {@inheritdoc}
     */
    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm();
        $basketElements = $event->getData();

        $this->buildBasketElements($form, $basketElements);
    }

    /**
     * @throws \Symfony\Component\Form\Exception\UnexpectedTypeException
     *
     * @param $form
     * @param $basketElements
     */
    private function buildBasketElements($form, $basketElements)
    {
        if (null === $basketElements) {
            return;
        }

        if (!$basketElements instanceof \ArrayAccess && !is_array($basketElements)) {
            throw new UnexpectedTypeException($basketElements, 'array or \ArrayAccess');
        }

        foreach ($basketElements as $basketElement) {
            $basketElementBuilder = $this->factory->createNamedBuilder($basketElement->getPosition(), 'form', $basketElement, array(
                'property_path'     => '['.$basketElement->getPosition().']',
                'auto_initialize'   => false,
            ));
            $basketElementBuilder->setErrorBubbling(false);

            $provider = $basketElement->getProductProvider();
            $provider->defineBasketElementForm($basketElement, $basketElementBuilder);

            $form->add($basketElementBuilder->getForm());
        }
    }

    /**
     * @param FormEvent $event
     */
    public function preBind(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

        $this->buildBasketElements($form, $this->basket->getBasketElements());
    }
}
