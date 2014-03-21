<?php if (!defined('APPLICATION')) exit();

$PluginInfo['jsconnectAutoSignIn'] = array(
   'Name' => 'Vanilla jsConnect Auto SignIn',
   'Description' => 'Forces sign in with the first available provider',
   'Version' => '0.1.8b',
   'RequiredApplications' => array('Vanilla' => '2.0.18'),
   'RequiredPlugins' => array('jsconnect' => '1.0.3b'),
   'MobileFriendly' => TRUE,
   'Author' => 'Paul Thomas',
   'AuthorEmail' => 'dt01pqt_pt@yahoo.com ',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/x00'
);

class jsConnectAutoSignInPlugin extends Gdn_Plugin {

  public function Base_Render_Before($Sender, $Args) {
    if (!Gdn::Session()->UserID && !in_array(strtolower($Sender->Request->Path()),array( 'entry/connect/jsconnect'))) {
      $Sender->AddCssFile('jsconnectAuto.css', 'plugins/jsconnectAutoSignIn');
      $Sender->AddJSFile('jsconnectAuto.js', 'plugins/jsconnectAutoSignIn');
      $Sender->AddDefinition('Connecting', T('jsconnectAutoSignIn.Connecting','Connecting...'));
      $Sender->AddDefinition('ConnectingUser', T('jsconnectAutoSignIn.ConnectingUser','Hi % just connecting you to forum...'));
      if (C('Plugins.jsConnectAutoSignIn.HideConnectButton') || IsMobile()) {
        $Sender->Head->AddString('<style type="text/css">.ConnectButton{display:none!important;}</style>');
      }
      $Providers = $this->GetProviders();
      if($Providers){
        $Sender->AddDefinition('JsConnectProviders', json_encode($Providers));
      }

    }else{
      if(in_array(strtolower($Sender->Request->Path()),array('entry/signin'))){
        $Target = $Sender->Request->Get('Target');
        if($Target)
          Redirect($Target);
      }
    }

    if (C('Plugins.jsConnectAutoSignIn.HideSignIn')) {
      $Sender->Head->AddString('<script type="text/javascript">' .
        'jQuery(document).ready(function($){' .
          '$(\'.ConnectButton,.SignInItem,a[href*="entry/signin"],a[href*="entry/signout"]\').hide();' .
        '});' .
        '</script>');
    }
  }
  //mobile and guest module-less friendly 
  public function GetProviders() {
    $Providers = JsConnectPlugin::GetProvider();
    $JsConnectProviders = array();
    foreach ($Providers as $Provider) {
      $Data = $Provider;
      $Target = Gdn::Request()->Get('Target');
      if (!$Target)
      $Target = '/'.ltrim(Gdn::Request()->Path());

      if (StringBeginsWith($Target, '/entry/signin'))
        $Target = '/';

      $ConnectQuery = array('client_id' => $Provider['AuthenticationKey'], 'Target' => $Target);
      $Data['Target'] = Url('entry/jsconnect', TRUE);
      if(strpos($Data['Target'],'?') !== FALSE) {
         $Data['Target'] .= '&'.http_build_query($ConnectQuery);
      } else {
         $Data['Target'] .= '?'.http_build_query($ConnectQuery);
      }
      $Data['Target'] = urlencode($Data['Target']);
      $Data['Name'] = Gdn_Format::Text($Data['Name']);
      $Data['SignInUrl'] = FormatString(GetValue('SignInUrl', $Provider, ''), $Data);
      $JsConnectProviders[] = $Data;
    }
    return empty($JsConnectProviders) ? FALSE: $JsConnectProviders;
  }

  public function EntryController_JsConnectAuto_Create($Sender, $Args) {
    $client_id = $Sender->SetData('client_id', $Sender->Request->Get('client_id', 0));
    $Provider = JsConnectPlugin::GetProvider($client_id);

    if (empty($Provider))
      throw NotFoundException('Provider');

    $Get = ArrayTranslate($Sender->Request->Get(), array('client_id', 'display'));

    $Sender->SetData('JsAuthenticateUrl', JsConnectPlugin::ConnectUrl($Provider, TRUE));
    $Sender->Render('JsConnect', '', 'plugins/jsconnect');
  }

}
