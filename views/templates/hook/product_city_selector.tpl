
<div class="bestqflow-selector card card-block mt-2 mb-2">
  <div class="bestqflow-title"><strong>{l s='Wybierz miasto / event' mod='bestqflow'}</strong></div>
  <div class="bestqflow-events">
    {foreach from=$bestqflow_events item=event}
      <label class="bestqflow-event-item d-block mb-1">
        <input type="radio" name="bestqflow_event_id" value="{$event.id_bestqflow_event|intval}" required>
        <span>
          <strong>{$event.city|escape:'html':'UTF-8'}</strong>
          — {$event.event_date|escape:'html':'UTF-8'}
          {if $event.event_time} | {$event.event_time|escape:'html':'UTF-8'}{/if}
          — {l s='pozostało' mod='bestqflow'}: {$event.remaining_qty|intval}
        </span>
      </label>
    {/foreach}
  </div>
  <div class="alert alert-info mt-2 mb-0">
    {l s='Maksymalna liczba biletów w jednym zamówieniu:' mod='bestqflow'} {$bestqflow_max_qty|intval}
  </div>
</div>
