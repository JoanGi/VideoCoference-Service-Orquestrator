<?php

namespace Drupal\videotrucades\Services\v1;

use Aws\Ec2\Ec2Client;
use Aws\Route53\Route53Client;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\Entity\User;
use Drupal\node\Entity\Node;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Notifications Manager.
 */
class JitsiManager {

  /**
   * Entity Manager Service.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The ec2Client to manage instances
   *
   * @var Aws\Ec2\Ec2Client
   */
  private $ec2Client;

  /**
   * The provisioning script for jitsi machines
   * @var string
   */
  private $provision_script = <<<SH
#!/bin/bash

echo 'Starting script' >> /home/ubuntu/provisioning.log
# install Docker
sudo apt update
sudo apt install -y apt-transport-https ca-certificates curl software-properties-common
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo apt-key add -
sudo add-apt-repository "deb [arch=amd64] https://download.docker.com/linux/ubuntu bionic stable"
sudo apt update
apt-cache policy docker-ce
sudo apt-get install -y docker-ce docker-ce-cli containerd.io
sudo service docker start
# sudo systemctl status docker

echo 'Docker done' >> /home/ubuntu/provisioning.log

# Install docker compose
sudo curl -L https://github.com/docker/compose/releases/download/1.21.2/docker-compose-`uname -s`-`uname -m` -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
docker-compose --version

echo 'Docker-compose done' >> /home/ubuntu/provisioning.log

# Pull code.
mkdir /home/ubuntu/workspace
cd /home/ubuntu/workspace
git clone https://github.com/jitsi/docker-jitsi-meet.git jitsi
chown -R ubuntu:ubuntu jitsi
cd jitsi

cp env.example .env
## Add custom settings for jitsi.
#sed -i 's/HTTP_PORT=8000/HTTP_PORT=80/g' .env
#sed -i 's/HTTPS_PORT=8443/HTTPS_PORT=443/g' .env
#echo "ENABLE_LETSENCRYPT=1" >> .env
#echo "ENABLE_HTTP_REDIRECT=1" >> .env
#echo "LETSENCRYPT_DOMAIN=SUBDOMAIN_PLACEHOLDER.trobada.eu" >> .env
#echo "LETSENCRYPT_EMAIL=giner.joan@gmail.com" >> .env

./gen-passwords.sh
mkdir -p /home/ubuntu/.jitsi-meet-cfg/{web/letsencrypt,transcripts,prosody,jicofo,jvb,jigasi,jibri}
chown -R ubuntu:ubuntu /home/ubuntu/.jitsi*
sudo docker-compose up -d
sudo docker-compose -f docker-compose.yml -f etherpad.yml up
sudo docker-compose -f docker-compose.yml -f jibri.yml up -d
echo 'jitsi done' >> ~/provisioning.log

wget http://ec2-3-249-33-80.eu-west-1.compute.amazonaws.com:4000/static/all.css
sudo docker cp all.css jitsi_web_1:/usr/share/jitsi-meet/css

echo 'jitsi personalization done' >> ~/provisioning.log

SH;

  /**
   * Constructor.
   *
   * @param Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity Manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    AccountProxyInterface $current_user
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $current_user;

    $this->ec2Client = new \Aws\Ec2\Ec2Client([
      'region' => 'eu-west-1',
      'version' => '2016-11-15',
      'profile' => 'default'
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    // Set dependecy injection.
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user'),
    );
  }

  /**
   * Create instance
   *
   * @param array $values
   *   Values from the content.
   */
  public function createInstance(array $values) {
    // Provisioning personalization for each jitsi instance.
    $subdomain = 'test1'; // Must com in the parameters
    $provision = str_replace('SUBDOMAIN_PLACEHOLDER', $values['subdomain'], $this->provision_script);

$response = $this->ec2Client->runInstances(array(
    'DryRun' => false,
    // ImageId is required
    'ImageId' => 'ami-0d1f717aa2de0a9d3',
    // MinCount is required
    'MinCount' => 1,
    // MaxCount is required
    'MaxCount' => 1,
    'LaunchTemplate' => array(
//	'LaunchTemplateId' => 'lt-0b80c0ae2e0aa922f',
        'LaunchTemplateName' => 'Jitsi-instance',
    ),
    'InstanceType' => 't2.micro',
    'InstanceInitiatedShutdownBehavior' => 'terminate',
    'UserData' => base64_encode($provision),
));

// Necessitem extreure la ipv4, 
kint($response);
die();

$route53_client = Route53Client::factory(array(
    'profile' => 'default'
));

/**
 *
$result = $client->changeResourceRecordSets(array(
    // HostedZoneId is required
    'HostedZoneId' => 'Z02412072GE54NJ5SISOK',
    // ChangeBatch is required
    'ChangeBatch' => array(
        'Comment' => 'string',
        // Changes is required
        'Changes' => array(
            array(
                // Action is required
                'Action' => 'CREATE',
                // ResourceRecordSet is required
                'ResourceRecordSet' => array(
                    // Name is required
                    'Name' => $subdomain . '.trobada.eu.',
                    // Type is required
                    'Type' => 'A',
                    'TTL' => 600,
                    'ResourceRecords' => array(
                        array(
                            // Value is required
                            'Value' => $response->get('PublicIpAddress'), // FALTA AIXÒ
                        ),
                    ),
                ),
            ),
        ),
    ),
));

 */
    return $response->get('InstanceId');
  }
  /**
   * Create instance
   *
   * @param array $values
   *   Values from the content.
   */
  public function UpdateInstance(array $values)
  {
    // Token is generated by app. You'll have to send the token to Drupal.
    kint('Updating Instancde');
    die();
  }
  /**
   * Delete instance
   *
   * @param array $values
   *   Values from the content.
   */
  public function deleteInstance(array $values)
  {
	  /**
	   * *IMportant*
	   * $instanceId ha de venir del node via values
	   * */
$instanceId='i-00a4d115967cc574e';
$this->ec2Client->stopInstances(array('Force' => true, 'InstanceIds' => array($instanceId)));
  }

}
