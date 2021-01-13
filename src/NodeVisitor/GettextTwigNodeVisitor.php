<?php

/*
 * This file based on one originally part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Acpr\I18n\NodeVisitor;

use Acpr\I18n\Node\TransNode;
use Twig\Environment;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\FilterExpression;
use Twig\Node\Expression\FunctionExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Node\Node;
use Twig\NodeVisitor\AbstractNodeVisitor;

/**
 * GettextNodeVisitor extracts translation messages.
 *
 * Altered slightly from the stock Twig Bridge node visitor so that it returns additional context
 * in the form of template line numbers when parsing messages out of twig templates.
 *
 * Removes transchoice as a type of node that can be visited as that is old syntax.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Adam Cooper <adam@acpr.dev>
 */
final class GettextTwigNodeVisitor extends AbstractNodeVisitor
{
    const UNDEFINED_DOMAIN = '_undefined';

    private bool $enabled = false;
    private array $messages = [];

    public function enable(): void
    {
        $this->enabled = true;
        $this->messages = [];
    }

    public function disable(): void
    {
        $this->enabled = false;
        $this->messages = [];
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * {@inheritdoc}
     */
    protected function doEnterNode(Node $node, Environment $env): Node
    {
        if (!$this->enabled) {
            return $node;
        }

        if (
            $node instanceof FunctionExpression &&
            in_array($node->getAttribute('name'), ['_', '__', '_n', '__n'])
        ) {
            $function = $env->getFunction($node->getAttribute('name'));
            $arguments = $function->getArguments();

            $value = $node->getNode('arguments')->getIterator()->current()->getAttribute('value');

//            // extract constant nodes with a trans filter
            $this->messages[] = [
                $value,
                null,
                'messages',
                null, # no notes yet.
                null, # no context either.
                $node->getTemplateLine()
            ];
        }

        return $node;
    }

    /**
     * {@inheritdoc}
     */
    protected function doLeaveNode(Node $node, Environment $env): ?Node
    {
        return $node;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority(): int
    {
        return 0;
    }
}