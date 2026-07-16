<?php

enum StatusLicitacao: string
{
    case AguardandoPublicacao = 'AGUARDANDO_PUBLICACAO';
    case Publicada = 'PUBLICADA';
    case Homologada = 'HOMOLOGADA';
    case EncaminhadaParaContratacao = 'ENCAMINHADA_PARA_CONTRATACAO';
}
