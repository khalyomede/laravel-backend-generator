<?php
	namespace Khalyomede\LaravelBackendGenerator;

	use PhpParser\Node;
	use PhpParser\NodeVisitorAbstract;

	class NodeVisitor extends NodeVisitorAbstract {
		public function beforeTraverse( $nodes ) {
			print_r($nodes);
		}
	}
?>