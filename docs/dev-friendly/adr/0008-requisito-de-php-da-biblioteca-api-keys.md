# Requisito de PHP da biblioteca API Keys

**Status:** aceito

O Gestão de Projetos exige PHP 8.3 para instalar e usar a biblioteca
`uspdev/api-keys`, conforme a versão mínima declarada por ela.

## Contexto

`uspdev/api-keys` declara PHP `^8.3` e Laravel 12 ou 13. O Gestão de Projetos
usa Laravel 12; reduzir artificialmente a restrição da biblioteca criaria uma
combinação sem suporte.

## Decisão

- O requisito PHP do Gestão de Projetos será `^8.3`.
- A aplicação consumirá o package respeitando seu contrato publicado, sem
  bifurcá-lo nem reduzir artificialmente sua versão mínima.
- A dependência será declarada como `uspdev/api-keys: ^0.1`, permitindo
  correções compatíveis da série `0.1.x` sem adotar automaticamente uma futura
  versão `0.2` potencialmente incompatível.
- O repositório irmão `../api-keys` será usado somente como referência durante
  o desenvolvimento. O `composer.json` da aplicação não configurará um
  repositório local do tipo `path`.
- Ambientes de desenvolvimento, integração e produção deverão executar PHP
  8.3 ou uma versão compatível com a restrição antes da implantação.

## Consequências

Ambientes ainda mantidos em PHP 8.2 deixarão de conseguir instalar as
dependências e precisarão ser atualizados. A elevação evita uma variante local
do package e mantém o Gestão de Projetos dentro da matriz oficialmente
suportada pela biblioteca.
