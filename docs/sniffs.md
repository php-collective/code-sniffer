# PhpCollective Code Sniffer


The PhpCollectiveStrict standard contains 235 sniffs

Generic (27 sniffs)
-------------------
- Generic.Arrays.ArrayIndent
- Generic.Arrays.DisallowLongArraySyntax
- Generic.CodeAnalysis.ForLoopShouldBeWhileLoop
- Generic.CodeAnalysis.ForLoopWithTestFunctionCall
- Generic.CodeAnalysis.JumbledIncrementer
- Generic.CodeAnalysis.UnconditionalIfStatement
- Generic.CodeAnalysis.UnnecessaryFinalModifier
- Generic.ControlStructures.InlineControlStructure
- Generic.Files.ByteOrderMark
- Generic.Files.LineEndings
- Generic.Files.LineLength
- Generic.Formatting.DisallowMultipleStatements
- Generic.Formatting.SpaceAfterCast
- Generic.Functions.FunctionCallArgumentSpacing
- Generic.NamingConventions.UpperCaseConstantName
- Generic.PHP.DeprecatedFunctions
- Generic.PHP.DisallowAlternativePHPTags
- Generic.PHP.DisallowShortOpenTag
- Generic.PHP.ForbiddenFunctions
- Generic.PHP.LowerCaseConstant
- Generic.PHP.LowerCaseKeyword
- Generic.PHP.LowerCaseType
- Generic.PHP.NoSilencedErrors
- Generic.WhiteSpace.DisallowTabIndent
- Generic.WhiteSpace.IncrementDecrementSpacing
- Generic.WhiteSpace.LanguageConstructSpacing
- Generic.WhiteSpace.ScopeIndent

Modernize (1 sniff)
-------------------
- Modernize.FunctionCalls.Dirname

NormalizedArrays (1 sniff)
--------------------------
- NormalizedArrays.Arrays.ArrayBraceSpacing

PEAR (4 sniffs)
---------------
- PEAR.Classes.ClassDeclaration
- PEAR.ControlStructures.ControlSignature
- PEAR.Functions.ValidDefaultValue
- PEAR.NamingConventions.ValidClassName

PhpCollective (82 sniffs)
-------------------------
- PhpCollective.Arrays.ArrayBracketSpacing
- PhpCollective.Arrays.DisallowImplicitArrayCreation
- PhpCollective.Classes.ClassFileName
- PhpCollective.Classes.EnumCaseCasing
- PhpCollective.Classes.MethodArgumentDefaultValue
- PhpCollective.Classes.MethodDeclaration
- PhpCollective.Classes.MethodTypeHint
- PhpCollective.Classes.PropertyDefaultValue
- PhpCollective.Classes.Psr4
- PhpCollective.Classes.ReturnTypeHint
- PhpCollective.Classes.SelfAccessor
- PhpCollective.Commenting.Attributes
- PhpCollective.Commenting.DisallowArrayTypeHintSyntax
- PhpCollective.Commenting.DisallowShorthandNullableTypeHint
- PhpCollective.Commenting.DocBlock
- PhpCollective.Commenting.DocBlockConst
- PhpCollective.Commenting.DocBlockConstructor
- PhpCollective.Commenting.DocBlockNoEmpty
- PhpCollective.Commenting.DocBlockNoInlineAlignment
- PhpCollective.Commenting.DocBlockParam
- PhpCollective.Commenting.DocBlockParamAllowDefaultValue
- PhpCollective.Commenting.DocBlockParamArray
- PhpCollective.Commenting.DocBlockParamNotJustNull
- PhpCollective.Commenting.DocBlockPipeSpacing
- PhpCollective.Commenting.DocBlockReturnNull
- PhpCollective.Commenting.DocBlockReturnNullableType
- PhpCollective.Commenting.DocBlockReturnSelf
- PhpCollective.Commenting.DocBlockReturnTag
- PhpCollective.Commenting.DocBlockReturnVoid
- PhpCollective.Commenting.DocBlockStructure
- PhpCollective.Commenting.DocBlockTag
- PhpCollective.Commenting.DocBlockTagGrouping
- PhpCollective.Commenting.DocBlockTagIterable
- PhpCollective.Commenting.DocBlockTagOrder
- PhpCollective.Commenting.DocBlockThrows
- PhpCollective.Commenting.DocBlockTypeOrder
- PhpCollective.Commenting.DocBlockVar
- PhpCollective.Commenting.DocBlockVarNotJustNull
- PhpCollective.Commenting.DocComment
- PhpCollective.Commenting.FileDocBlock
- PhpCollective.Commenting.FullyQualifiedClassNameInDocBlock
- PhpCollective.Commenting.InlineDocBlock
- PhpCollective.Commenting.TypeHint
- PhpCollective.ControlStructures.ConditionalExpressionOrder
- PhpCollective.ControlStructures.ControlStructureEmptyStatement
- PhpCollective.ControlStructures.ControlStructureSpacing
- PhpCollective.ControlStructures.DisallowAlternativeControlStructures
- PhpCollective.ControlStructures.DisallowCloakingCheck
- PhpCollective.ControlStructures.ElseIfDeclaration
- PhpCollective.ControlStructures.NoInlineAssignment
- PhpCollective.Formatting.ArrayDeclaration
- PhpCollective.Formatting.MethodSignatureParametersLineBreakMethod
- PhpCollective.Internal.DisallowFunctions
- PhpCollective.Namespaces.FunctionNamespace
- PhpCollective.Namespaces.UseStatement
- PhpCollective.Namespaces.UseWithAliasing
- PhpCollective.PHP.DeclareStrictTypes
- PhpCollective.PHP.DisallowFunctions
- PhpCollective.PHP.DisallowTrailingCommaInSingleLine
- PhpCollective.PHP.Exit
- PhpCollective.PHP.NoIsNull
- PhpCollective.PHP.NotEqual
- PhpCollective.PHP.PhpSapiConstant
- PhpCollective.PHP.PreferCastOverFunction
- PhpCollective.PHP.RemoveFunctionAlias
- PhpCollective.PHP.ShortCast
- PhpCollective.PHP.SingleQuote
- PhpCollective.Testing.AssertPrimitives
- PhpCollective.Testing.ExpectException
- PhpCollective.Testing.Mock
- PhpCollective.WhiteSpace.CommaSpacing
- PhpCollective.WhiteSpace.ConcatenationSpacing
- PhpCollective.WhiteSpace.DocBlockSpacing
- PhpCollective.WhiteSpace.EmptyEnclosingLine
- PhpCollective.WhiteSpace.EmptyLines
- PhpCollective.WhiteSpace.FunctionSpacing
- PhpCollective.WhiteSpace.ImplicitCastSpacing
- PhpCollective.WhiteSpace.MemberVarSpacing
- PhpCollective.WhiteSpace.MethodSpacing
- PhpCollective.WhiteSpace.NamespaceSpacing
- PhpCollective.WhiteSpace.ObjectAttributeSpacing
- PhpCollective.WhiteSpace.TernarySpacing

PhpCollectiveStrict (3 sniffs)
------------------------------
- PhpCollectiveStrict.TypeHints.ParameterTypeHint
- PhpCollectiveStrict.TypeHints.PropertyTypeHint
- PhpCollectiveStrict.TypeHints.ReturnTypeHint

PSR1 (3 sniffs)
---------------
- PSR1.Classes.ClassDeclaration
- PSR1.Files.SideEffects
- PSR1.Methods.CamelCapsMethodName

PSR2 (12 sniffs)
----------------
- PSR2.Classes.ClassDeclaration
- PSR2.Classes.PropertyDeclaration
- PSR2.ControlStructures.ControlStructureSpacing
- PSR2.ControlStructures.ElseIfDeclaration
- PSR2.ControlStructures.SwitchDeclaration
- PSR2.Files.ClosingTag
- PSR2.Files.EndFileNewline
- PSR2.Methods.FunctionCallSignature
- PSR2.Methods.FunctionClosingBrace
- PSR2.Methods.MethodDeclaration
- PSR2.Namespaces.NamespaceDeclaration
- PSR2.Namespaces.UseDeclaration

PSR12 (14 sniffs)
-----------------
- PSR12.Classes.AnonClassDeclaration
- PSR12.Classes.ClassInstantiation
- PSR12.Classes.ClosingBrace
- PSR12.Classes.OpeningBraceSpace
- PSR12.ControlStructures.BooleanOperatorPlacement
- PSR12.ControlStructures.ControlStructureSpacing
- PSR12.Files.ImportStatement
- PSR12.Functions.NullableTypeDeclaration
- PSR12.Functions.ReturnTypeDeclaration
- PSR12.Keywords.ShortFormTypeKeywords
- PSR12.Namespaces.CompoundNamespaceDepth
- PSR12.Operators.OperatorSpacing
- PSR12.Properties.ConstantVisibility
- PSR12.Traits.UseDeclaration

SlevomatCodingStandard (55 sniffs)
----------------------------------
- SlevomatCodingStandard.Arrays.ArrayAccess
- SlevomatCodingStandard.Arrays.MultiLineArrayEndBracketPlacement
- SlevomatCodingStandard.Arrays.SingleLineArrayWhitespace
- SlevomatCodingStandard.Arrays.TrailingArrayComma
- SlevomatCodingStandard.Attributes.AttributeAndTargetSpacing
- SlevomatCodingStandard.Attributes.RequireAttributeAfterDocComment
- SlevomatCodingStandard.Classes.BackedEnumTypeSpacing
- SlevomatCodingStandard.Classes.ClassConstantVisibility
- SlevomatCodingStandard.Classes.ClassMemberSpacing
- SlevomatCodingStandard.Classes.ConstantSpacing
- SlevomatCodingStandard.Classes.EnumCaseSpacing
- SlevomatCodingStandard.Classes.ModernClassNameReference
- SlevomatCodingStandard.Commenting.DeprecatedAnnotationDeclaration
- SlevomatCodingStandard.Commenting.DisallowOneLinePropertyDocComment
- SlevomatCodingStandard.Commenting.EmptyComment
- SlevomatCodingStandard.Commenting.InlineDocCommentDeclaration
- SlevomatCodingStandard.ControlStructures.AssignmentInCondition
- SlevomatCodingStandard.ControlStructures.DisallowContinueWithoutIntegerOperandInSwitch
- SlevomatCodingStandard.ControlStructures.DisallowYodaComparison
- SlevomatCodingStandard.ControlStructures.JumpStatementsSpacing
- SlevomatCodingStandard.ControlStructures.LanguageConstructWithParentheses
- SlevomatCodingStandard.ControlStructures.NewWithParentheses
- SlevomatCodingStandard.ControlStructures.RequireNullCoalesceOperator
- SlevomatCodingStandard.ControlStructures.RequireShortTernaryOperator
- SlevomatCodingStandard.Exceptions.DeadCatch
- SlevomatCodingStandard.Functions.ArrowFunctionDeclaration
- SlevomatCodingStandard.Functions.DisallowTrailingCommaInCall
- SlevomatCodingStandard.Functions.DisallowTrailingCommaInClosureUse
- SlevomatCodingStandard.Functions.DisallowTrailingCommaInDeclaration
- SlevomatCodingStandard.Functions.NamedArgumentSpacing
- SlevomatCodingStandard.Functions.RequireTrailingCommaInCall
- SlevomatCodingStandard.Functions.RequireTrailingCommaInClosureUse
- SlevomatCodingStandard.Functions.RequireTrailingCommaInDeclaration
- SlevomatCodingStandard.Namespaces.AlphabeticallySortedUses
- SlevomatCodingStandard.Namespaces.FullyQualifiedClassNameInAnnotation
- SlevomatCodingStandard.Namespaces.NamespaceDeclaration
- SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly
- SlevomatCodingStandard.Namespaces.RequireOneNamespaceInFile
- SlevomatCodingStandard.Namespaces.UnusedUses
- SlevomatCodingStandard.Namespaces.UseDoesNotStartWithBackslash
- SlevomatCodingStandard.Namespaces.UseFromSameNamespace
- SlevomatCodingStandard.Namespaces.UselessAlias
- SlevomatCodingStandard.Namespaces.UseSpacing
- SlevomatCodingStandard.Operators.SpreadOperatorSpacing
- SlevomatCodingStandard.PHP.ForbiddenClasses
- SlevomatCodingStandard.PHP.ShortList
- SlevomatCodingStandard.PHP.TypeCast
- SlevomatCodingStandard.PHP.UselessSemicolon
- SlevomatCodingStandard.TypeHints.DNFTypeHintFormat
- SlevomatCodingStandard.TypeHints.LongTypeHints
- SlevomatCodingStandard.TypeHints.NullableTypeForNullDefaultValue
- SlevomatCodingStandard.TypeHints.ParameterTypeHintSpacing
- SlevomatCodingStandard.TypeHints.ReturnTypeHintSpacing
- SlevomatCodingStandard.Variables.DuplicateAssignmentToVariable
- SlevomatCodingStandard.Whitespaces.DuplicateSpaces

Squiz (27 sniffs)
-----------------
- Squiz.Arrays.ArrayBracketSpacing
- Squiz.Classes.LowercaseClassKeywords
- Squiz.Classes.ValidClassName
- Squiz.Commenting.DocCommentAlignment
- Squiz.ControlStructures.ControlSignature
- Squiz.ControlStructures.ForEachLoopDeclaration
- Squiz.ControlStructures.ForLoopDeclaration
- Squiz.ControlStructures.LowercaseDeclaration
- Squiz.Functions.FunctionDeclaration
- Squiz.Functions.FunctionDeclarationArgumentSpacing
- Squiz.Functions.LowercaseFunctionKeywords
- Squiz.Functions.MultiLineFunctionDeclaration
- Squiz.Operators.ValidLogicalOperators
- Squiz.PHP.DisallowSizeFunctionsInLoops
- Squiz.PHP.Eval
- Squiz.PHP.NonExecutableCode
- Squiz.Scope.MemberVarScope
- Squiz.Scope.MethodScope
- Squiz.Scope.StaticThisUsage
- Squiz.WhiteSpace.CastSpacing
- Squiz.WhiteSpace.ControlStructureSpacing
- Squiz.WhiteSpace.FunctionOpeningBraceSpace
- Squiz.WhiteSpace.LogicalOperatorSpacing
- Squiz.WhiteSpace.ScopeClosingBrace
- Squiz.WhiteSpace.ScopeKeywordSpacing
- Squiz.WhiteSpace.SemicolonSpacing
- Squiz.WhiteSpace.SuperfluousWhitespace

Universal (5 sniffs)
--------------------
- Universal.Constants.LowercaseClassResolutionKeyword
- Universal.Constants.UppercaseMagicConstants
- Universal.Operators.ConcatPosition
- Universal.UseStatements.NoUselessAliases
- Universal.WhiteSpace.PrecisionAlignment

Zend (1 sniff)
--------------
- Zend.Files.ClosingTag
