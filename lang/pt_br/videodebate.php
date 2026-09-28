<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings em Português do Brasil.
 *
 * @package mod_videodebate
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['accessibilityheader'] = 'Acessibilidade';
$string['addcurrentmoment'] = 'Adicionar momento atual';
$string['allowseek'] = 'Permitir avançar para partes ainda não assistidas';
$string['argument'] = 'Argumento';
$string['arguments'] = 'Argumentos';
$string['assignmentautomatic'] = 'Distribuir posições automaticamente';
$string['assignmentfree'] = 'Escolha livre';
$string['assignmentmode'] = 'Distribuição das posições';
$string['backtodebate'] = 'Voltar ao debate';
$string['blinduntilpost'] = 'Exigir posição própria antes de visualizar os colegas';
$string['cannotreplygroup'] = 'Você não pode responder a uma contribuição fora do seu grupo.';
$string['cannotreplyself'] = 'Você não pode responder ao próprio argumento.';
$string['captionfile'] = 'Arquivo de legendas WebVTT';
$string['captionlang'] = 'Idioma da legenda';
$string['captions'] = 'Legendas';
$string['completiondetail:percent'] = 'Assistir pelo menos {$a}% do vídeo';
$string['completiondetail:post'] = 'Publicar um argumento inicial no debate';
$string['completiondetail:replies'] = 'Publicar pelo menos {$a} respostas aos colegas';
$string['completionpercent'] = 'Percentual mínimo assistido';
$string['completionpost'] = 'Exigir argumento inicial';
$string['completionreplies'] = 'Quantidade mínima de respostas';
$string['completionrules'] = 'Regras de conclusão';
$string['criterion:argumentation'] = 'Argumentação (0–100)';
$string['criterion:evidence'] = 'Uso de evidências (0–100)';
$string['criterion:participation'] = 'Participação (0–100)';
$string['criterion:replies'] = 'Respostas aos colegas (0–100)';
$string['debate'] = 'Debate';
$string['debateheader'] = 'Debate';
$string['debatequestion'] = 'Questão de debate';
$string['defaultpositions'] = 'Concordo
Discordo
Parcialmente';
$string['deletepost'] = 'Excluir';
$string['durationnotconfigured'] = 'A duração do vídeo ainda não foi configurada pelo professor.';
$string['durationseconds'] = 'Duração do vídeo em segundos';
$string['durationseconds_help'] = 'Duração autoritativa usada pelo servidor para calcular o percentual assistido. Esse valor impede que o navegador defina sozinho o denominador da conclusão.';
$string['editpost'] = 'Editar';
$string['errorcaptionlang'] = 'Informe um código de idioma válido, por exemplo en, pt-BR ou es.';
$string['errordurationrequired'] = 'Informe a duração do vídeo em segundos.';
$string['errorgrade'] = 'A nota máxima deve estar entre 0 e 100.';
$string['errorinvalidurl'] = 'Informe uma URL de vídeo HTTP ou HTTPS válida.';
$string['errorinvalidvimeo'] = 'Informe uma URL ou ID numérico de vídeo válido do Vimeo.';
$string['errorinvalidyoutube'] = 'Informe uma URL ou ID de vídeo válido do YouTube.';
$string['errornonnegative'] = 'Informe zero ou um número positivo.';
$string['errorpercent'] = 'O percentual deve estar entre 0 e 100.';
$string['errorpositions'] = 'Informe pelo menos duas posições diferentes.';
$string['errorresetvideodata'] = 'Esta atividade já possui dados de participantes. Confirme o reset antes de trocar o vídeo ou sua duração.';
$string['errorvideorequired'] = 'Selecione um arquivo de vídeo para a fonte de vídeo enviado.';
$string['errorweights'] = 'Os quatro pesos da avaliação devem totalizar exatamente 100%.';
$string['eventgradeupdated'] = 'Nota do participante atualizada';
$string['eventpostcreated'] = 'Argumento inicial criado';
$string['eventreplycreated'] = 'Resposta do debate criada';
$string['eventreportviewed'] = 'Relatório de participação visualizado';
$string['evidence'] = 'Evidências';
$string['evidenceadded'] = 'Evidência adicionada.';
$string['evidencedescription'] = 'Descrição da evidência';
$string['evidenceempty'] = 'Nenhuma evidência adicionada.';
$string['evidencetimeline'] = 'Timeline de evidências';
$string['evidencetitle'] = 'Evidências do vídeo';
$string['feedback'] = 'Feedback';
$string['finishinterval'] = 'Finalizar intervalo';
$string['grade'] = 'Nota';
$string['gradeparticipant'] = 'Avaliar {$a}';
$string['gradesaved'] = 'Nota salva.';
$string['gradingheader'] = 'Avaliação';
$string['gradingsummary'] = 'Evidências utilizadas: {$a->evidence}. Respostas publicadas: {$a->replies}.';
$string['hiddenpost'] = 'Oculto';
$string['hidepost'] = 'Ocultar';
$string['initialpostexists'] = 'Você já publicou seu argumento inicial.';
$string['invalidposition'] = 'A posição selecionada é inválida.';
$string['lastaccess'] = 'Última atividade no vídeo';
$string['maximumgrade'] = 'Nota máxima';
$string['messagebody:grade'] = 'Sua atividade {$a->activity} foi avaliada: {$a->grade} / {$a->maximum}.';
$string['messagebody:reply'] = '{$a->author} respondeu ao seu argumento em {$a->activity}.';
$string['messageprovider:gradenotification'] = 'Notificações de avaliação';
$string['messageprovider:replynotification'] = 'Notificações de respostas';
$string['messagesubject:grade'] = 'Nota no Video Debate: {$a}';
$string['messagesubject:reply'] = 'Nova resposta no Video Debate: {$a}';
$string['minevidence'] = 'Quantidade mínima de evidências no argumento inicial';
$string['moderationupdated'] = 'A contribuição foi atualizada.';
$string['modulename'] = 'Video Debate';
$string['modulename_help'] = 'Cria um debate acadêmico no qual posições e respostas precisam usar evidências de momentos específicos do vídeo.';
$string['modulenameplural'] = 'Video Debates';
$string['nestedreplynotallowed'] = 'As respostas só podem ser publicadas diretamente em um argumento inicial.';
$string['noposts'] = 'Nenhum argumento foi publicado ainda.';
$string['nostudents'] = 'Nenhum estudante participante foi encontrado.';
$string['notenoughevidence'] = 'Adicione pelo menos {$a} evidência(s) do vídeo antes de publicar.';
$string['notpublished'] = 'Não publicado';
$string['orphanedreplies'] = 'Respostas cujo argumento original foi removido';
$string['parentremoved'] = 'O argumento original desta resposta não está mais disponível.';
$string['participation'] = 'Participação';
$string['pluginname'] = 'Video Debate';
$string['position'] = 'Posição';
$string['positionpublished'] = 'Sua posição publicada:';
$string['positions'] = 'Posições';
$string['positions_help'] = 'Informe uma posição por linha. São necessárias pelo menos duas posições.';
$string['postmessage'] = 'Texto da contribuição';
$string['postsaved'] = 'Sua contribuição foi publicada.';
$string['postupdated'] = 'A contribuição foi atualizada.';
$string['privacy:metadata:evidence'] = 'Armazena momentos e intervalos do vídeo citados como evidência.';
$string['privacy:metadata:evidence:endtime'] = 'Fim da evidência em segundos.';
$string['privacy:metadata:evidence:label'] = 'Descrição da evidência pelo participante.';
$string['privacy:metadata:evidence:starttime'] = 'Início da evidência em segundos.';
$string['privacy:metadata:evidence:timecreated'] = 'Momento em que a referência de evidência foi criada.';
$string['privacy:metadata:grades'] = 'Armazena critérios, nota e feedback do professor.';
$string['privacy:metadata:grades:argumentation'] = 'Nota do critério argumentação.';
$string['privacy:metadata:grades:evidence'] = 'Nota do critério uso de evidências.';
$string['privacy:metadata:grades:feedback'] = 'Feedback do professor.';
$string['privacy:metadata:grades:finalgrade'] = 'Nota final calculada.';
$string['privacy:metadata:grades:graderid'] = 'Professor que realizou a avaliação.';
$string['privacy:metadata:grades:participation'] = 'Nota do critério participação.';
$string['privacy:metadata:grades:replies'] = 'Nota do critério respostas.';
$string['privacy:metadata:grades:timemodified'] = 'Momento da última atualização da nota.';
$string['privacy:metadata:grades:userid'] = 'Usuário avaliado.';
$string['privacy:metadata:posts'] = 'Armazena argumentos e respostas publicados pelos participantes.';
$string['privacy:metadata:posts:groupid'] = 'Grupo associado à contribuição.';
$string['privacy:metadata:posts:hidden'] = 'Indica se um moderador ocultou a contribuição.';
$string['privacy:metadata:posts:message'] = 'Texto do argumento ou resposta.';
$string['privacy:metadata:posts:parentid'] = 'Argumento pai de uma resposta.';
$string['privacy:metadata:posts:position'] = 'Posição escolhida ou atribuída no argumento inicial.';
$string['privacy:metadata:posts:positionlabel'] = 'Rótulo histórico da posição no momento da publicação.';
$string['privacy:metadata:posts:timecreated'] = 'Momento em que a contribuição foi publicada.';
$string['privacy:metadata:posts:timemodified'] = 'Momento da última alteração da contribuição.';
$string['privacy:metadata:posts:userid'] = 'Usuário que publicou a contribuição.';
$string['privacy:metadata:progress'] = 'Armazena o progresso assistido do vídeo.';
$string['privacy:metadata:progress:completed'] = 'Indica se as regras de conclusão foram atingidas.';
$string['privacy:metadata:progress:duration'] = 'Duração autoritativa do vídeo usada no progresso.';
$string['privacy:metadata:progress:lastposition'] = 'Última posição de reprodução.';
$string['privacy:metadata:progress:percent'] = 'Percentual único assistido.';
$string['privacy:metadata:progress:segments'] = 'Segmentos do vídeo confirmados como assistidos.';
$string['privacy:metadata:progress:timemodified'] = 'Momento da última atualização do progresso.';
$string['privacy:metadata:progress:totalwatchtime'] = 'Total de segundos assistidos aceitos.';
$string['privacy:metadata:progress:uniquewatched'] = 'Segundos únicos assistidos.';
$string['privacy:metadata:progress:userid'] = 'Usuário cujo progresso é armazenado.';
$string['publishargument'] = 'Publicar argumento';
$string['publishbeforeview'] = 'Publique sua própria posição e argumentação antes de visualizar as contribuições dos colegas.';
$string['publishreply'] = 'Publicar resposta';
$string['remove'] = 'Remover';
$string['replies'] = 'Respostas';
$string['reply'] = 'Responder';
$string['replytargethidden'] = 'Você não pode responder a uma contribuição oculta.';
$string['report'] = 'Relatório de participação';
$string['requiredpercent'] = 'Obrigatório: {$a}% assistido';
$string['resetgrades'] = 'Excluir notas e feedbacks do Video Debate';
$string['resetposts'] = 'Excluir argumentos, respostas e evidências do Video Debate';
$string['resetprogress'] = 'Excluir progresso de vídeo do Video Debate';
$string['resetvideodata'] = 'Resetar dados dos participantes porque o vídeo mudou';
$string['resetvideodata_help'] = 'Trocar o vídeo ou sua duração invalida o progresso assistido e as evidências temporais. Ao selecionar, progresso, evidências e notas armazenadas são apagados.';
$string['resumeplayback'] = 'Retomar da última posição assistida';
$string['savegrade'] = 'Salvar nota';
$string['scorebetween'] = 'Informe uma nota de 0 a 100.';
$string['seekblocked'] = 'Assista à parte anterior antes de avançar para um ponto ainda não assistido.';
$string['showpost'] = 'Exibir';
$string['sourceupload'] = 'Vídeo enviado';
$string['sourceurl'] = 'URL direta do vídeo';
$string['sourcevimeo'] = 'Vimeo';
$string['sourceyoutube'] = 'YouTube';
$string['startinterval'] = 'Iniciar intervalo';
$string['student'] = 'Estudante';
$string['trackingerror'] = 'Não foi possível salvar o progresso do vídeo. A reprodução pode continuar e o plugin tentará novamente na próxima atualização.';
$string['transcript'] = 'Transcrição';
$string['videodebate:addinstance'] = 'Adicionar uma atividade Video Debate';
$string['videodebate:grade'] = 'Avaliar participantes';
$string['videodebate:moderate'] = 'Editar, ocultar e excluir contribuições do Video Debate';
$string['videodebate:participate'] = 'Publicar argumento inicial';
$string['videodebate:reply'] = 'Responder aos colegas';
$string['videodebate:view'] = 'Visualizar Video Debate';
$string['videodebate:viewall'] = 'Visualizar todas as contribuições';
$string['videodebate:viewreport'] = 'Visualizar relatórios';
$string['videodebatename'] = 'Nome do Video Debate';
$string['videofile'] = 'Arquivo de vídeo';
$string['videoheader'] = 'Vídeo';
$string['videoplayer'] = 'Player de vídeo';
$string['videoprogress'] = 'Progresso do vídeo';
$string['videosource'] = 'Fonte do vídeo';
$string['videourl'] = 'URL ou ID do vídeo';
$string['weightargument'] = 'Peso da argumentação (%)';
$string['weightevidence'] = 'Peso do uso de evidências (%)';
$string['weightparticipation'] = 'Peso da participação (%)';
$string['weightreplies'] = 'Peso das respostas (%)';
$string['yourgrade'] = 'Sua nota';
$string['yourposition'] = 'Sua posição';
